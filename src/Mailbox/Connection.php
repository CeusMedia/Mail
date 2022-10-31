<?php
declare(strict_types=1);

/**
 *	Connection handler for IMAP mailboxes.
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
namespace CeusMedia\Mail\Mailbox;

use CeusMedia\Mail\Mailbox;

use InvalidArgumentException;
use RuntimeException;

use function count;
use function imap_close;
use function imap_open;
use function imap_ping;
use function imap_timeout;
use function in_array;
use function is_resource;
use function join;
use function strlen;
use function trim;

/**
 *	Connection handler for IMAP mailboxes.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Connection
{
	/**
	 * @var resource|NULL $resource
	 */
	protected $resource						= NULL;

	/**
	 * @var string $username
	 */
	protected string $username;

	/**
	 * @var string $password
	 */
	protected string $password;

	/**
	 * @var string $host
	 */
	protected string $host;

	/**
	 * @var int $port
	 */
	protected int $port						= 143;

	/**
	 * @var string|NULL $reference
	 */
	protected ?string $reference			= NULL;

	/**
	 * @var bool $secure
	 */
	protected bool $secure					= TRUE;

	/**
	 * @var bool $validateCertificates
	 */
	protected bool $validateCertificates	= TRUE;

	/**
	 * @var string|NULL $error
	 */
	protected ?string $error				= NULL;

	public function __construct( string $host, string $username = '', string $password = '', bool $secure = TRUE, bool $validateCertificates = TRUE )
	{
		Mailbox::checkExtensionInstalled();
		$this->setHost( $host );
		if( 0 < strlen( trim( $username ) ) && 0 < strlen( trim( $password ) ) )
			$this->setAuth( $username, $password );
		$this->setSecure( $secure, $validateCertificates );
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	public function connect( bool $strict = TRUE ): bool
	{
		$reference	= $this->renderReference();
		$options	= $this->secure && $this->validateCertificates ? OP_SECURE : 0;
		$uri		= $reference.'INBOX';
		$resource	= @imap_open( $uri, $this->username, $this->password, $options );
		if( FALSE !== $resource ){
			$this->resource	= $resource;
			return TRUE;
		}
		if( FALSE !== imap_last_error() )
			$this->error	= imap_last_error();
		if( $strict )
			throw new RuntimeException( 'Connection to server failed: '.$this->error );
		return FALSE;
	}

	public function disconnect(): bool
	{
		if( NULL !== $this->resource ){
			if( is_resource( $this->resource ) )
				imap_close( $this->resource, CL_EXPUNGE );
			$this->resource	= NULL;
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

	/**
	 *	...
	 *	@param		bool		$connect
	 *	@return		resource
	 */
	public function getResource( bool $connect = FALSE )
	{
		if( !$this->isOpen() ){
			if( !$connect )
				throw new RuntimeException( 'Not connected' );
			if( !$this->connect() )
				throw new RuntimeException( 'Connection failed' );
		}
		if( NULL === $this->resource )
			throw new RuntimeException( 'Connection resource has gone away' );
		return $this->resource;
	}

	public function isOpen( bool $ping = TRUE ): bool
	{
		if( !is_resource( $this->resource ) )
			return FALSE;
		if( $ping && !imap_ping( $this->resource ) )
			return FALSE;
		return TRUE;
	}

	public function renderReference( bool $withPortAndFlags = TRUE ): string
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

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for method chaining
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
	 *	@return		self		Own instance for method chaining
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
	public function setSecure( bool $secure = TRUE, bool $validateCertificates = TRUE ): self
	{
		$this->secure				= $secure;
		$this->validateCertificates	= $validateCertificates;
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		self		Own instance for method chaining
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

	//  --  PROTECTED  --  //
}
