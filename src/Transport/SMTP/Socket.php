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
	/**	@var	string|NULL			$host */
	protected ?string $host			= NULL;

	/**	@var	int|NULL			$port */
	protected ?int $port			= NULL;

	/**	@var	integer				$errorNumber */
	protected int $errorNumber		= 0;

	/**	@var	string				$errorMessage */
	protected string $errorMessage	= '';

	/** @var	resource|NULL		$connection */
	protected $connection			= NULL;

	/**	@var	integer				$timeout */
	protected int $timeout			= 5;

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
	 *	@codeCoverageIgnore
	 *	@noinspection PhpDocMissingThrowsInspection
	 */
	public static function create( string $host = NULL, int $port = NULL, int $timeout = NULL ): self
	{
		/** @noinspection PhpUnhandledExceptionInspection */
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
	public function __construct( string $host = NULL, int $port = NULL, ?int $timeout = NULL )
	{
		if( !is_null( $host ) && strlen( trim( $host ) ) > 0 )
			$this->setHost( $host );
		if( is_integer( $port ) )
			$this->setPort( abs( $port ) );
		if( is_integer( $timeout ) )
			$this->setTimeout( abs( $timeout ) );
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
			$this->sendChunk( "QUIT" );
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
	 *	Indicates whether a socket connection has been established.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isOpen(): bool
	{
		return NULL !== $this->connection;
	}

	/**
	 *	Open socket connection.
	 *	@access		public
	 *	@param		boolean			$forceReopen	Flag: close current and open new connection (default: no)
	 *	@return		Response
	 */
	public function open( bool $forceReopen = FALSE ): Response
	{
		if( $this->isOpen() ){
			if( !$forceReopen )
				return new Response();
			$this->close();
		}
		$response	= new Response();
		if( NULL === $this->host || 0 === strlen( trim( $this->host ) ) ){
			$response->setError( Response::ERROR_NO_HOST_SET );
			$response->setMessage( 'No host set' );
			return $response;
		}
		if( NULL === $this->port || 0 === $this->port ){
			$response->setError( Response::ERROR_NO_PORT_SET );
			$response->setMessage( 'No port set' );
			return $response;
		}

		$socket		= @fsockopen(
			$this->host,
			$this->port,
			$this->errorNumber,
			$this->errorMessage,
			$this->timeout
		);
		if( FALSE === $socket ){
			$message	= 'Socket connection to SMTP server "%s" at port %d failed';
			$response->setMessage( sprintf( $message, $this->host, $this->port ) );
			$response->setError( Response::ERROR_SOCKET_FAILED );
			return $response;
		}
		$this->connection	= $socket;
		$response			= $this->readResponse();
		if( 220 !== $response->getCode() ){
			$response->setError( Response::ERROR_CONNECTION_FAILED );
			$this->close();
		}

		return $response;
	}

	/**
	 *	Returns parsed response from SMTP server.
	 *	@access		public
	 *	@param		integer			$length			Size of chunks
	 *	@throws		RuntimeException				if connection is not open
	 *	@throws		RuntimeException				if request failed
	 *	@return		Response
	 */
	public function readResponse( int $length = 1024 ): Response
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'Not connected, thus reading response failed' );

		$lastLine	= FALSE;
		$code		= NULL;
		$buffer		= [];
		$raw		= [];
		do{
			$chunk	= fgets( $this->connection, abs( $length ) );
			if( FALSE !== $chunk ){
				$raw[]		= rtrim( $chunk, "\r\n" );
				$matches	= [];
				preg_match( '/^([0-9]{3})( |-)(.+)$/', trim( $chunk ), $matches );
				if( 0 === count( $matches ) ){
					$response = new Response();
					$response->setError( Response::ERROR_RESPONSE_NOT_UNDERSTOOD );
					$response->setMessage( 'SMTP response not understood: '.trim( $chunk ) );
					return $response;
				}
				$code		= (int) $matches[1];
				$buffer[]	= $matches[3];
				$lastLine	= $matches[2] === ' ';
			}
		}
		while( FALSE !== $chunk && !$lastLine );

		$response	= new Response( $code );
		$response->setMessage( join( "\n", $buffer ) );
		$response->setResponse( $raw );
		return $response;
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
