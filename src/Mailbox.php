<?php
declare(strict_types=1);

/**
 *	Handler for IMAP mailboxes.
 *
 *	Copyright (c) 2017-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use CeusMedia\Mail\Conduct\RegularStringHandling;
use CeusMedia\Mail\Message\Parser as MessageParser;
use CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Mailbox\Connection as MailboxConnection;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;

use RangeException;
use ReflectionException;
use RuntimeException;

use function extension_loaded;
use function imap_body;
use function imap_clearflag_full;
use function imap_delete;
use function imap_expunge;
use function imap_fetchheader;
use function imap_last_error;
use function imap_mail_move;
use function imap_setflag_full;
use function imap_sort;
use function in_array;
use function join;
use function preg_quote;
use function strtolower;
use function ucfirst;

/**
 *	Handler for IMAP mailboxes.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Mailbox
{
	use RegularStringHandling;

	/**
	 * @var MailboxConnection $connection
	 */
	protected MailboxConnection $connection;

	/**
	 * @var string|NULL $reference
	 */
	protected ?string $reference		= NULL;

	public function __construct( MailboxConnection $connection )
	{
		self::checkExtensionInstalled();
		$this->connection	= $connection;
	}

	public function __destruct()
	{
		$this->connection->disconnect();
	}

	public static function checkExtensionInstalled( bool $strict = TRUE ): bool
	{
		if( extension_loaded( 'imap' ) )
			return TRUE;
		if( $strict )
			throw new RuntimeException( 'PHP extension "imap" not installed, but needed' );
		return FALSE;
	}

	public static function getInstance( MailboxConnection $connection ): self
	{
		return new self( $connection );
	}

	/**
	 *	@param		bool		$recursive
	 *	@param		bool		$fullReference
	 *	@return		array
	 */
	public function getFolders( bool $recursive = FALSE, bool $fullReference = FALSE ): array
	{
		$pattern	= $recursive ? '*' : '%';
		$resource	= $this->connection->getResource( TRUE );
		$reference	= $this->connection->renderReference();
		$folders	= imap_list( $resource, $reference, $pattern );
		if( FALSE === $folders ){
			if( FALSE !== imap_last_error() )
				throw new RuntimeException( imap_last_error() );
			throw new RuntimeException( 'Listing folders failed' );
		}
		if( !$fullReference ){
			$regExp	= '/^'.preg_quote( $reference, '/' ).'/';
			foreach( $folders as $nr => $folder )
				$folders[$nr]	= self::regReplace( $regExp, '', $folder );
		}
		foreach( $folders as $nr => $folder )
			$folders[$nr]	= mb_convert_encoding( $folder, 'UTF-8', 'UTF7-IMAP' );
		natcasesort($folders);
		return $folders;
	}

	public function getMail( int $mailId ): string
	{
		$resource	= $this->connection->getResource( TRUE );
		$header		= imap_fetchheader( $resource, $mailId, FT_UID );
		if( FALSE === $header )
			throw new RuntimeException( 'Invalid mail ID' );
		$body		= imap_body( $resource, $mailId, FT_UID | FT_PEEK );
		return $header.PHP_EOL.PHP_EOL.$body;
	}

	/**
	 *	@param		int			$mailId
	 *	@return		Message
	 *	@throws		ReflectionException
	 */
	public function getMailAsMessage( int $mailId ): Message
	{
		$resource	= $this->connection->getResource( TRUE );
		$header		= imap_fetchheader( $resource, $mailId, FT_UID );
		if( FALSE === $header )
			throw new RuntimeException( 'Invalid mail ID' );
		$body		= imap_body( $resource, $mailId, FT_UID | FT_PEEK );
		return MessageParser::getInstance()->parse( $header.PHP_EOL.PHP_EOL.$body );
	}

	/**
	 *	@param		int			$mailId
	 *	@return		MessageHeaderSection
	 */
	public function getMailHeaders( int $mailId ): MessageHeaderSection
	{
		$resource	= $this->connection->getResource( TRUE );
		$header		= imap_fetchheader( $resource, $mailId, FT_UID );
		if( FALSE === $header )
			throw new RuntimeException( 'Invalid mail ID' );
		return MessageHeaderParser::getInstance()->parse( $header );
	}

	public function index( array $criteria = [], int $sort = SORTARRIVAL, bool $reverse = TRUE ): array
	{
		$resource	= $this->connection->getResource( TRUE );
		$result		= imap_sort( $resource, $sort, $reverse, SE_UID, join( ' ', $criteria ), 'UTF-8' );
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
		$resource	= $this->connection->getResource( TRUE );
		$folder		= mb_convert_encoding( $folder, 'UTF7-IMAP', 'UTF-8' );
		$result		= imap_mail_move( $resource, join( ',', $mailIds ), $folder, CP_UID );
		if( $expunge )
			imap_expunge( $resource );
		return $result;
	}

	/**
	 *	@param		MailboxSearch		$search
	 *	@return		array
	 */
	public function performSearch( MailboxSearch $search ): array
	{
		$resource	= $this->connection->getResource( TRUE );
		$search->setConnection( $resource );
		return $search->getAll();
	}

	/**
	 *	@param		int			$mailId
	 *	@param		bool		$expunge
	 *	@return		bool
	 */
	public function removeMail( int $mailId, bool $expunge = FALSE ): bool
	{
		return $this->removeMailsBySequence( (string) $mailId, $expunge );
	}

	/**
	 *	@param		string		$sequence
	 *	@param		bool		$expunge
	 *	@return		bool
	 */
	public function removeMailsBySequence( string $sequence, bool $expunge = FALSE ): bool
	{
		$resource	= $this->connection->getResource( TRUE );
		$result		= imap_delete( $resource, $sequence, FT_UID );
		if( $expunge )
			imap_expunge( $resource );
		return $result;
	}

	public function search( array $conditions ): array
	{
		$resource	= $this->connection->getResource( TRUE );
		return MailboxSearch::getInstance()
			->setConnection( $resource )
			->applyConditions( $conditions )
			->getAll();
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for method chaining
	 *	@todo		code doc
	 */
	public function setMailFlag( string $mailId, string $flag ): self
	{
		$resource	= $this->connection->getResource( TRUE );
		$flags		= [ 'seen', 'answered', 'flagged', 'deleted', 'draft' ];
		if( !in_array( strtolower( $flag ), $flags, TRUE ) )
			throw new RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag		= '\\'.ucfirst( strtolower( $flag ) );
		imap_setflag_full( $resource, $mailId, $flag, ST_UID );
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for method chaining
	 *	@todo		code doc
	 */
	public function unsetMailFlag( string $mailId, string $flag ): self
	{
		$resource	= $this->connection->getResource( TRUE );
		$flags		= [ 'seen', 'answered', 'flagged', 'deleted', 'draft' ];
		if( !in_array( strtolower( $flag ), $flags, TRUE ) )
			throw new RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag		= '\\'.ucfirst( strtolower( $flag ) );
		imap_clearflag_full( $resource, $mailId, $flag, ST_UID );
		return $this;
	}

	//  --  PROTECTED  --  //
}
