<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2007-2025 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport\SMTP;

use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Util\Str;

use RuntimeException;

use function abs;
use function error_clear_last;
use function error_get_last;
use function fclose;
use function fgets;
use function filter_var;
use function fwrite;
use function is_integer;
use function join;
use function max;
use function preg_replace;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function stream_context_create;
use function stream_get_meta_data;
use function stream_set_timeout;
use function stream_socket_client;
use function stream_socket_enable_crypto;
use function strlen;
use function trim;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://www.knownhost.com/wiki/email/troubleshooting/error-numbers
 *	@see			https://www.serversmtp.com/en/smtp-error
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

	/**	@var	integer				$readTimeout	Timeout (in seconds) on waiting for a response */
	protected int $readTimeout		= 30;

	/** @var    integer|NULL        $implicitCrypto     Crypto mode for TLS right after connecting (SMTPS, port 465), NULL: none */
	protected ?int $implicitCrypto	= NULL;

	/**
	 *	@var	array<string,mixed>	$sslOptions		SSL context options for TLS (implicit and STARTTLS)
	 *	@see	https://www.php.net/manual/en/context.ssl.php
	 */
	protected array $sslOptions		= [];

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
	public function __construct( string $host = NULL, int $port = NULL, ?int $timeout = NULL )
	{
		if( Str::hasContent( $host ) )
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
			//  connection may be closed by server already: no warning (broken pipe), also for custom error handlers
			set_error_handler( static fn(): bool => TRUE, E_WARNING | E_NOTICE );
			try{
				fwrite( $this->connection, "QUIT\r\n" );								//  commands end with CRLF (RFC 5321, 2.3.8)
				fclose( $this->connection );
			}
			finally{
				restore_error_handler();
			}
			$this->connection	= NULL;
		}
		return $this;
	}

	/**
	 *	Enables TLS right after connecting, before the server greets (implicit TLS, SMTPS on port 465).
	 *	For STARTTLS (port 587) use enableCrypto() after the STARTTLS command instead.
	 *	@access		public
	 *	@param		boolean			$enable		Power switch
	 *	@param		integer			$crypto		Cryptography mode, default: STREAM_CRYPTO_METHOD_ANY_CLIENT
	 *	@return		self
	 */
	public function setImplicitTls( bool $enable, int $crypto = STREAM_CRYPTO_METHOD_ANY_CLIENT ): self
	{
		$this->implicitCrypto	= $enable ? $crypto : NULL;
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
		error_clear_last();
		$result	= @stream_socket_enable_crypto( $this->connection, $enable, $crypto );
		if( TRUE !== $result )
			throw new RuntimeException( 'Crypto negotiation failed'.$this->getLastPhpError() );
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
     *	Returns local address of the open connection, eg. "192.0.2.1:54321" or "[2001:db8::1]:54321".
     *	@access		public
     *	@return		string|NULL		NULL if not connected
     */
    public function getLocalAddress(): ?string
    {
        if( NULL === $this->connection )
            return NULL;
        $name	= stream_socket_get_name( $this->connection, FALSE );
        return FALSE !== $name ? $name : NULL;
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
	 *	Returns set timeout on waiting for a response.
	 *	@access		public
	 *	@return		integer
	 */
	public function getReadTimeout(): int
	{
		return $this->readTimeout;
	}

	/**
	 *	Returns set SSL context options.
	 *	@access		public
	 *	@return		array<string,mixed>
	 */
	public function getSslOptions(): array
	{
		return $this->sslOptions;
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
		if( !Str::hasContent( $this->host ) ){
			$response->setError( Response::ERROR_NO_HOST_SET );
			$response->setMessage( 'No host set' );
			return $response;
		}
		if( NULL === $this->port || 0 === $this->port ){
			$response->setError( Response::ERROR_NO_PORT_SET );
			$response->setMessage( 'No port set' );
			return $response;
		}

		//  stream context holds SSL options, used later on implicit TLS and STARTTLS as well
		$context	= stream_context_create( ['ssl' => $this->sslOptions] );
		$socket		= @stream_socket_client(
			$this->getRemoteSocket(),
			$this->errorNumber,
			$this->errorMessage,
			$this->timeout,
			STREAM_CLIENT_CONNECT,
			$context
		);
		if( FALSE === $socket ){
			$message	= sprintf( 'Socket connection to SMTP server "%s" at port %d failed', $this->host, $this->port );
			if( Str::hasContent( $this->errorMessage ) )
				$message	.= ': '.trim( $this->errorMessage );						//  after sprintf, may contain "%"
			$response->setMessage( $message );
			$response->setError( Response::ERROR_SOCKET_FAILED );
			return $response;
		}
		stream_set_timeout( $socket, $this->readTimeout );
		$this->connection	= $socket;
		if( NULL !== $this->implicitCrypto ){
			//  implicit TLS: the server greets only after the TLS handshake
			error_clear_last();
			if( TRUE !== @stream_socket_enable_crypto( $socket, TRUE, $this->implicitCrypto ) ){
				fclose( $socket );
				$this->connection	= NULL;
				$message	= 'TLS negotiation with SMTP server "%s" at port %d failed';
				$response->setMessage( sprintf( $message, $this->host, $this->port ).$this->getLastPhpError() );
				$response->setError( Response::ERROR_CRYPTO_FAILED );
				return $response;
			}
		}
		$response			= $this->readResponse();
		if( 220 !== $response->getCode() ){
			$message	= sprintf( 'No greeting from SMTP server "%s" at port %d', $this->host, $this->port );
			if( 0 !== strlen( $response->getMessage() ) )
				$message	.= ': '.$response->getMessage();
			if( Response::ERROR_TIMEOUT === $response->getError() && NULL === $this->implicitCrypto )
				$message	.= ' (port may expect implicit TLS)';
			$response->setMessage( $message );
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
			if( FALSE === $chunk ){
				$response	= new Response();
				if( stream_get_meta_data( $this->connection )['timed_out'] ){
					$response->setError( Response::ERROR_TIMEOUT );
					$response->setMessage( sprintf( 'No response from SMTP server within %d seconds', $this->readTimeout ) );
				}
				else{
					$response->setError( Response::ERROR_SOCKET_EXCEPTION );
					$response->setMessage( 'Connection closed by SMTP server' );
				}
				$response->setResponse( $raw );
				return $response;
			}
			$raw[]		= rtrim( $chunk, "\r\n" );
			if( !Str::regMatch( '/^([0-9]{3})([ \-])(.+)$/', trim( $chunk ), $matches ) ){
				$response = new Response();
				$response->setError( Response::ERROR_RESPONSE_NOT_UNDERSTOOD );
				$response->setMessage( 'SMTP response not understood: '.trim( $chunk ) );
				return $response;
			}
			$code		= (int) $matches[1];
			$buffer[]	= $matches[3];
			$lastLine	= $matches[2] === ' ';
		}
		while( !$lastLine );

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

	/**
	 *	Set timeout (in seconds) on waiting for a response, applied on next opening.
	 *	@access		public
	 *	@param		integer		$seconds	Timeout (in seconds) on waiting for a response
	 *	@return		self
	 */
	public function setReadTimeout( int $seconds ): self
	{
		$this->readTimeout	= max( 1, $seconds );
		return $this;
	}

	/**
	 *	Set SSL context options for TLS, applied on next opening.
	 *	Example: ['verify_peer' => FALSE, 'verify_peer_name' => FALSE] or ['cafile' => '/path/to/ca.pem'].
	 *	@access		public
	 *	@param		array<string,mixed>		$options	SSL context options
	 *	@return		self
	 *	@see		https://www.php.net/manual/en/context.ssl.php
	 */
	public function setSslOptions( array $options ): self
	{
		$this->sslOptions	= $options;
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Returns message of last PHP error, e.g. of a failed TLS handshake, as suffix for an error message.
	 *	@access		protected
	 *	@return		string		Empty if no error occurred
	 */
	protected function getLastPhpError(): string
	{
		$error	= error_get_last();
		if( NULL === $error )
			return '';
		$message	= trim( (string) preg_replace( '/^[a-z_]+\(\): /', '', $error['message'] ) );
		return 0 !== strlen( $message ) ? ': '.$message : '';
	}

	/**
	 *	Returns address for socket client: transport, host (IPv6 in brackets) and port.
	 *	@access		protected
	 *	@return		string
	 */
	protected function getRemoteSocket(): string
	{
		$host	= (string) $this->host;
		if( str_contains( $host, '://' ) )
			return $host.':'.$this->port;
		if( FALSE !== filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
			$host	= '['.$host.']';
		return 'tcp://'.$host.':'.$this->port;
	}
}
