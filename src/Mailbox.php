<?php
declare(strict_types=1);

/**
 *	Handler for IMAP mailboxes.
 *
 *	Copyright (c) 2017-2022 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use CeusMedia\Mail\Message\Parser as MessageParser;
use CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;

use InvalidArgumentException;
use RangeException;
use RuntimeException;

use function count;
use function extension_loaded;
use function imap_body;
use function imap_clearflag_full;
use function imap_close;
use function imap_delete;
use function imap_expunge;
use function imap_fetchheader;
use function imap_last_error;
use function imap_mail_move;
use function imap_open;
use function imap_ping;
use function imap_setflag_full;
use function imap_sort;
use function imap_timeout;
use function in_array;
use function is_resource;
use function join;
use function preg_replace;
use function preg_quote;
use function strlen;
use function strtolower;
use function trim;
use function ucfirst;

/**
 *	Handler for IMAP mailboxes.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Mailbox
{
	/**
	 * @var resource|NULL $connection
	 */
	protected $connection;

	/**
	 * @var string $username
	 */
	protected $username;

	/**
	 * @var string $password
	 */
	protected $password;

	/**
	 * @var string $host
	 */
	protected $host;

	/**
	 * @var int $port
	 */
	protected $port						= 143;

	/**
	 * @var string|NULL $reference
	 */
	protected $reference;

	/**
	 * @var bool $secure
	 */
	protected $secure					= TRUE;

	/**
	 * @var bool $validateCertificates
	 */
	protected $validateCertificates		= TRUE;

	/**
	 * @var string $error
	 */
	protected $error;

	public function __construct( string $host, string $username = '', string $password = '', bool $secure = TRUE, bool $validateCertificates = TRUE )
	{
		self::checkExtensionInstalled( TRUE );
		$this->setHost( $host );
		if( 0 < strlen( trim( $username ) ) && 0 < strlen( trim( $password ) ) )
			$this->setAuth( $username, $password );
		$this->setSecure( $secure, $validateCertificates );
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	public static function checkExtensionInstalled( bool $strict = TRUE ): bool
	{
		if( extension_loaded( 'imap' ) )
			return TRUE;
		if( $strict )
			throw new RuntimeException( 'PHP extension "imap" not installed, but needed' );
		return FALSE;
	}

	public function connect( bool $strict = TRUE ): bool
	{
		$reference	= $this->renderConnectionReference( TRUE );
		$options	= $this->secure && $this->validateCertificates ? OP_SECURE : 0;
		$uri		= $reference.'INBOX';
		$resource	= @imap_open( $uri, $this->username, $this->password, $options );
		if( FALSE !== $resource ){
			$this->connection	= $resource;
			return TRUE;
		}
		$this->error	= imap_last_error();
		if( $strict )
			throw new RuntimeException( 'Connection to server failed: '.$this->error );
		return FALSE;
	}

	public function disconnect(): bool
	{
		if( $this->checkConnection( FALSE, FALSE ) ){
			$result	= imap_close( $this->connection, CL_EXPUNGE );
			$this->connection	= NULL;
			return $result;
		}
		return TRUE;
	}

	public static function getInstance( string $host, string $username = '', string $password = '', bool $secure = TRUE, bool $validateCertificates = TRUE ): self
	{
		return new self( $host, $username, $password, $secure, $validateCertificates );
	}

	public function getError(): ?string
	{
		return $this->error;
	}

	public function getFolders( bool $recursive = NULL, bool $fullReference = FALSE ): array
	{
		$pattern	= $recursive ? '*' : '%';
		$reference	= $this->renderConnectionReference( TRUE );
		$folders		= imap_list( $this->connection, $reference, $pattern );
		if( FALSE === $folders )
			throw new RuntimeException( imap_last_error() );
		if( !$fullReference ){
			$regExp	= '/^'.preg_quote( $reference, '/' ).'/';
			foreach( $folders as $nr => $folder )
				$folders[$nr]	= preg_replace( $regExp, '', $folder );
		}
		foreach( $folders as $nr => $folder )
			$folders[$nr]	= mb_convert_encoding( $folder, 'UTF-8', 'UTF7-IMAP' );
		natcasesort($folders);
		return $folders;
	}

	public function getMail( int $mailId, bool $strict = TRUE ): string
	{
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( FALSE === $header )
			throw new RuntimeException( 'Invalid mail ID' );
		$body	= imap_body( $this->connection, $mailId, FT_UID | FT_PEEK );
		return $header.PHP_EOL.PHP_EOL.$body;
	}

	public function getMailAsMessage( int $mailId, bool $strict = TRUE ): Message
	{
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( FALSE === $header )
			throw new RuntimeException( 'Invalid mail ID' );
		$body	= imap_body( $this->connection, $mailId, FT_UID | FT_PEEK );
		return MessageParser::getInstance()->parse( $header.PHP_EOL.PHP_EOL.$body );
	}

	public function getMailHeaders( int $mailId, bool $strict = TRUE ): MessageHeaderSection
	{
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( FALSE === $header )
			throw new RuntimeException( 'Invalid mail ID' );
		return MessageHeaderParser::getInstance()->parse( $header );
	}

	public function index( array $criteria = [], int $sort = SORTARRIVAL, bool $reverse = TRUE, bool $strict = TRUE ): array
	{
		$this->checkConnection( TRUE, $strict );
		$result	= imap_sort( $this->connection, $sort, (int) $reverse, SE_UID, join( ' ', $criteria ), 'UTF-8' );
		if( $result === FALSE )
			$result	= [];
		return $result;
	}

	/**
	 *	Moves one mail to another folder.
	 *	@access		public
	 *	@param		integer		$mailId		Mail UID
	 *	@param		string		$folder		Target folder, encoded as UTF-8, will be encoded to UTF-7-IMAP internally
	 *	@param		boolean		$expunge	Flag: apply change immediately, default: no
	 *	@return		boolean
	 */
	public function moveMail( int $mailId, string $folder, bool $expunge = FALSE ): bool
	{
		return $this->moveMails( [$mailId], $folder, $expunge );
	}

	/**
	 *	Moves several mails to another folder.
	 *	@access		public
	 *	@param		array		$mailIds	List of mail UIDs
	 *	@param		string		$folder		Target folder, encoded as UTF-8, will be encoded to UTF-7-IMAP internally
	 *	@param		boolean		$expunge	Flag: apply change immediately, default: no
	 *	@return		boolean
	 */
	public function moveMails( array $mailIds, string $folder, bool $expunge = FALSE ): bool
	{
		$this->checkConnection( TRUE );
		$folder	= mb_convert_encoding( $folder, 'UTF7-IMAP', 'UTF-8' );
		$result	= imap_mail_move( $this->connection, join( ',', $mailIds ), $folder, CP_UID );
		if( $expunge )
			imap_expunge( $this->connection );
		return $result;
	}

	public function performSearch( MailboxSearch $search ): array
	{
		$this->checkConnection( TRUE, TRUE );
		$search->setConnection( $this->connection );
		return $search->getAll();
	}

	public function removeMail( int $mailId, bool $expunge = FALSE ): bool
	{
		return $this->removeMailsBySequence( (string) $mailId, $expunge );
	}

	public function removeMailsBySequence( string $sequence, bool $expunge = FALSE ): bool
	{
		$this->checkConnection( TRUE );
		$result	= imap_delete( $this->connection, $sequence, FT_UID );
		if( $expunge )
			imap_expunge( $this->connection );
		return $result;
	}

	public function search( array $conditions ): array
	{
		$this->checkConnection( TRUE, TRUE );
		return MailboxSearch::getInstance()
			->setConnection( $this->connection )
			->applyConditions( $conditions )
			->getAll();
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setAuth( string $username, string $password ): self
	{
		$this->username	= $username;
		$this->password	= $password;
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setHost( string $host ): self
	{
		$this->host	= $host;
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setMailFlag( string $mailId, string $flag ): self
	{
		$flags	= [ 'seen', 'answered', 'flagged', 'deleted', 'draft' ];
		if( !in_array( strtolower( $flag ), $flags, TRUE ) )
			throw new RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag	= '\\'.ucfirst( strtolower( $flag ) );
		imap_setflag_full( $this->connection, $mailId, $flag, ST_UID );
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setSecure( bool $secure = TRUE, bool $validateCertificates = TRUE ): self
	{
		$this->secure				= $secure;
		$this->validateCertificates	= $validateCertificates;
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setTimeout( int $type, int $seconds ): self
	{
		$timeoutTypes	= [
			IMAP_OPENTIMEOUT,
			IMAP_READTIMEOUT,
			IMAP_WRITETIMEOUT,
			IMAP_CLOSETIMEOUT
		];
		if( !in_array( $type, $timeoutTypes, TRUE ) )
			throw new InvalidArgumentException( 'Invalid timeout type' );
		imap_timeout( $timeoutTypes[$type], $seconds );
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for chainability
	 *	@todo		code doc
	 */
	public function unsetMailFlag( string $mailId, string $flag ): self
	{
		$flags	= [ 'seen', 'answered', 'flagged', 'deleted', 'draft' ];
		if( !in_array( strtolower( $flag ), $flags, TRUE ) )
			throw new RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag	= '\\'.ucfirst( strtolower( $flag ) );
		imap_clearflag_full( $this->connection, $mailId, $flag, ST_UID );
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	...
	 *	@param		bool		$connect
	 *	@param		bool		$strict
	 *	@return		bool
	 */
	protected function checkConnection( bool $connect = FALSE, bool $strict = TRUE ): bool
	{
		if( NULL === $this->connection || !imap_ping( $this->connection ) ){
			if( !$connect ){
				if( $strict )
					throw new RuntimeException( 'Not connected' );
				return FALSE;
			}
			return $this->connect();
		}
		if( NULL !== $this->connection && is_resource( $this->connection ) )
			return TRUE;
		if( $strict )
			throw new RuntimeException( 'Not connected' );
		return FALSE;
	}

	protected function renderConnectionReference( bool $withPortAndFlags = TRUE ): string
	{
		if( !$withPortAndFlags )
			return '{'.$this->host.'}';
		if( NULL === $this->reference ){
			$port		= 143;
//			$flags		= ['imap'];
			$flags		= [];
			if( $this->secure ){
				$port		= 993;
				$flags[]	= 'ssl';
				if( $this->validateCertificates ){
					$flags[]	= 'validate-cert';
				}
			}
			if( !$this->validateCertificates )
				$flags[]	= 'novalidate-cert';
			$flags	= 0 !== count( $flags ) ? '/'.join( '/', $flags ) : '';
			$this->reference	= '{'.$this->host.':'.$port.$flags.'}';
		}
		return $this->reference;
	}
}
