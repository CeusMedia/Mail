<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2007-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport\SMTP;

use CeusMedia\Mail\Deprecation;

use RuntimeException;

use function abs;
use function fclose;
use function fgets;
use function fsockopen;
use function fwrite;
use function is_integer;
use function is_null;
use function preg_match;
use function join;
use function stream_socket_enable_crypto;
use function strlen;
use function trim;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://www.knownhost.com/wiki/email/troubleshooting/error-numbers
 *	@see			http://www.serversmtp.com/en/smtp-error
 */
class Socket
{
	/**	@var	string|NULL		$host */
	protected $host;

	/**	@var	int|NULL		$port */
	protected $port;

	/**	@var	integer			$errorNumber */
	protected $errorNumber		= 0;

	/**	@var	string			$errorMessage */
	protected $errorMessage		= '';

	/** @var	resource|NULL	$connection */
	protected $connection;

	/**	@var	integer			$timeout */
	protected $timeout			= 5;

	/**
	 *	Alias for getInstance.
	 *	@access		public
	 *	@static
	 *	@param		string|NULL		$host		SMTP server host name
	 *	@param		integer|NULL	$port		SMTP server port
	 *	@param		integer|NULL	$timeout	Timeout (in seconds) on opening connection
	 *	@return		self
	 *	@deprecated	use getInstance instead
	 *	@todo		to be removed
	 */
	public static function create( string $host = NULL, int $port = NULL, int $timeout = NULL ): self
	{
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method getInstance instead' );
		return static::getInstance( $host, $port, $timeout );
	}

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string|NULL		$host			SMTP server host name
	 *	@param		integer|NULL	$port			SMTP server port
	 *	@param		integer|NULL	$timeout		Timeout (in seconds) on opening connection
	 *	@return		void
	 */
	public function __construct( string $host = NULL, int $port = NULL, $timeout = NULL )
	{
		if( !is_null( $host ) && strlen( trim( $host ) ) > 0 )
			$this->setHost( $host );
		if( is_integer( $port ) )
			$this->setPort( abs( $port ) );
		if( is_integer( $timeout ) )
			$this->setPort( abs( $timeout ) );
	}

	public function __destruct()
	{
		$this->close();
	}

	/**
	 *	Closes open connection.
	 *	@access		public
	 *	@return		self
	 */
	public function close(): self
	{
		if( NULL !== $this->connection ){
			fclose( $this->connection );
			$this->connection	= NULL;
		}
		return $this;
	}

	/**
	 *	Sets cryptography mode.
	 *	@access		public
	 *	@param		boolean			$enable		Power switch
	 *	@param		integer			$crypto		Cryptography mode, default: STREAM_CRYPTO_METHOD_ANY_CLIENT, @see https://www.php.net/manual/en/function.stream-socket-enable-crypto.php
	 *	@throws		RuntimeException			if connection is not open
	 *	@return		self
	 */
	public function enableCrypto( bool $enable, int $crypto = STREAM_CRYPTO_METHOD_ANY_CLIENT ): self
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'Not connected' );
		$result	= stream_socket_enable_crypto( $this->connection, $enable, $crypto );
		if( FALSE === $result )
			throw new RuntimeException( 'Crypto negotiation failed' );
		return $this;
	}

	/**
	 *	Returns last error as map set (array) of number and message.
	 *	@access		public
	 *	@return		array
	 */
	public function getError(): array
	{
		return [
			'number'	=> $this->errorNumber,
			'message'	=> $this->errorMessage,
		];
	}

	/**
	 *	Returns last error message.
	 *	@return		string
	 */
	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}

	/**
	 *	Returns last error number.
	 *	@return		integer
	 */
	public function getErrorNumber(): int
	{
		return $this->errorNumber;
	}

	/**
	 *	Returns set host.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getHost(): ?string
	{
		return $this->host;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@param		string|NULL		$host		SMTP server host name
	 *	@param		integer|NULL	$port		SMTP server port
	 *	@param		integer|NULL	$timeout	Timeout (in seconds) on opening connection
	 *	@return		self
	 */
	public static function getInstance( string $host = NULL, int $port = NULL, int $timeout = NULL ): self
	{
		return new self( $host, $port, $timeout );
	}

	/**
	 *	Returns set port.
	 *	@access		public
	 *	@return		integer|NULL
	 */
	public function getPort(): ?int
	{
		return $this->port;
	}

	/**
	 *	Returns set timeout.
	 *	@access		public
	 *	@return		integer
	 */
	public function getTimeout(): int
	{
		return $this->timeout;
	}

	/**
	 *	Open socket connection.
	 *	@access		public
	 *	@param		boolean			$forceReopen	Flag: close current and open new connection (default: no)
	 *	@throws		RuntimeException				if not host is set
	 *	@throws		RuntimeException				if not port is set
	 *	@throws		RuntimeException				if connection failed
	 *	@return		self
	 */
	public function open( bool $forceReopen = FALSE ): self
	{
		if( NULL !== $this->connection ){
			if( $forceReopen )
				$this->close();
			else
				return $this;
		}
		if( NULL === $this->host || 0 === strlen( trim( $this->host ) ) )
			throw new RuntimeException( 'No host set' );
		if( NULL === $this->port || 0 === $this->port )
			throw new RuntimeException( 'No port set' );
		$socket	= fsockopen(
			$this->host,
			$this->port,
			$this->errorNumber,
			$this->errorMessage,
			$this->timeout
		);
		if( FALSE === $socket )
			throw new RuntimeException( 'Connection to SMTP server "'.$this->host.':'.$this->port.'" failed' );
		$this->connection	= $socket;
		return $this;
	}

	/**
	 *	Returns parsed response from SMTP server.
	 *	@access		public
	 *	@param		integer			$length			Size of chunks
	 *	@throws		RuntimeException				if connection is not open
	 *	@throws		RuntimeException				if request failed
	 *	@return		object
	 */
	public function readResponse( int $length )
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'Not connected' );

		$lastLine	= FALSE;
		$code		= NULL;
		$buffer		= [];
		$raw		= [];
		do{
			$response	= fgets( $this->connection, abs( $length ) );
			if( FALSE !== $response ){
				$raw[]		= rtrim( $response, "\r\n" );
				$matches	= [];
				preg_match( '/^([0-9]{3})( |-)(.+)$/', trim( $response ), $matches );
				if( !$matches )
					throw new RuntimeException( 'SMTP response not understood: '.trim( $response ) );
				$code		= (int) $matches[1];
				$buffer[]	= $matches[3];
				$lastLine	= $matches[2] === ' ';
			}
		}
		while( FALSE !== $response && !$lastLine );
		return (object) [
			'code'		=> $code,
			'message'	=> join( "\n", $buffer ),
			'raw'		=> $raw,
		];
	}

	/**
	 *	Sends command or data to SMTP server.
	 *	@access		public
	 *	@param		string		$content	Message to send to SMTP server
	 *	@return		boolean					Number of written bytes
	 *	@throws		RuntimeException		if connection is not open
	 */
	public function sendChunk( string $content ): bool
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'Not connected' );
		return FALSE !== fwrite( $this->connection, $content );
	}

	/**
	 *	Set host.
	 *	@access		public
	 *	@param		string		$host		SMTP server host name
	 *	@return		self
	 */
	public function setHost( string $host ): self
	{
		$this->host	= $host;
		return $this;
	}

	/**
	 *	Set port.
	 *	@access		public
	 *	@param		integer		$port		SMTP server port
	 *	@return		self
	 */
	public function setPort( int $port ): self
	{
		$this->port	= $port;
		return $this;
	}

	/**
	 *	Set timeout (in seconds) on opening connection.
	 *	@access		public
	 *	@param		integer		$seconds	Timeout (in seconds) on opening connection
	 *	@return		self
	 */
	public function setTimeout( int $seconds ): self
	{
		$this->timeout	= $seconds;
		return $this;
	}
}
