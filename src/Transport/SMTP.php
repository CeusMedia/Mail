<?php
declare(strict_types=1);

/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
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
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer as MessageRenderer;
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;
use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\Mail\Transport\SMTP\Response as SmtpResponse;
use CeusMedia\Mail\Transport\SMTP\Socket as SmtpSocket;
use CeusMedia\Mail\Util\Str;

use InvalidArgumentException;
use RangeException;
use RuntimeException;

use Throwable;
use function array_filter;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function join;
use function strtolower;

/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://www.der-webdesigner.net/tutorials/php/anwendungen/329-php-und-oop-mailversand-via-smtp.html
 */
class SMTP
{
	/**	@var	string				$host		SMTP server host name */
	protected string $host;

	/**	@var	integer				$port		SMTP server port */
	protected int $port				= 25;

	/**	@var	string				$username	SMTP auth username */
	protected string $username		= '';

	/**	@var	string				$password	SMTP auth password */
	protected string $password		= '';

	/**	@var	boolean				$isSecure		Flag: encrypt connection (STARTTLS or implicit TLS) */
	protected bool $isSecure		= FALSE;

	/**	@var	boolean				$isImplicitTls	Flag: TLS right after connecting (SMTPS, port 465) instead of STARTTLS */
	protected bool $isImplicitTls	= FALSE;

	/**	@var	boolean				$validateCertificate	Flag: validate server certificate on TLS */
	protected bool $validateCertificate	= TRUE;

	/**
	 *	@var	array<string,mixed>	$sslOptions		SSL context options, override certificate validation flag
	 *	@see	https://www.php.net/manual/en/context.ssl.php
	 */
	protected array $sslOptions		= [];

	/**
	 *	@var	array<string,mixed>|NULL	$socketSslOptions	SSL context options of socket before first use, base for own options
	 */
	protected ?array $socketSslOptions	= NULL;

	/**	@var	integer|NULL		$readTimeout	Timeout (in seconds) on waiting for a response, NULL: default of socket */
	protected ?int $readTimeout		= NULL;

	/**	@var	boolean				$verbose */
	protected bool $verbose			= FALSE;

    /**	@var	string|NULL			$heloHost		Name of this client for EHLO, NULL: detect */
    protected ?string $heloHost		= NULL;

    /**	@var	SmtpSocket|NULL		$socket */
	protected ?SmtpSocket $socket	= NULL;

	/**
	 * 	@var	integer				$cryptoMode		Cryptography mode, default: STREAM_CRYPTO_METHOD_ANY_CLIENT
	 *	@see	https://www.php.net/manual/en/function.stream-socket-enable-crypto.php
	 */
	protected int $cryptoMode		= STREAM_CRYPTO_METHOD_ANY_CLIENT;

	/**
	 *	Get instance of SMTP transport.
	 *	Receives connection parameters (host, port, username, password) if given.
	 *	@static
	 *	@access		public
	 *	@param		string		$host		SMTP server host name
	 *	@param		integer		$port		SMTP server port
	 *	@param		string		$username	SMTP auth username
	 *	@param		string		$password	SMTP auth password
	 *	@return		self
	 */
	public static function getInstance( string $host, int $port = 25, string $username = '', string $password = '' ): self
	{
		return new self( $host, $port, $username, $password );
	}

	/**
	 *	Constructor.
	 *	Receives connection parameters (host, port, username, password) if given.
	 *
	 *	@access		public
	 *	@param		string		$host		SMTP server host name
	 *	@param		integer		$port		SMTP server port
	 *	@param		string		$username	SMTP auth username
	 *	@param		string		$password	SMTP auth password
	 *	@return		void
	 */
	public function __construct( string $host, int $port = 25, string $username = '', string $password = '' )
	{
		$this->setHost( $host );
		$this->setPort( $port );
		$this->setUsername( $username );
		$this->setPassword( $password );
	}

	/**
	 *	Returns last connection error message, if connecting failed.
	 *	Otherwise, NULL.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getConnectError(): ?string
	{
		if( NULL !== $this->socket ){
			$socketError	= $this->socket->getError()['message'];
			if( Str::hasContent( $socketError ) )
				return $socketError;
		}
		return NULL;
	}

	/**
	 *	Sends mail using a socket connection to a remote SMTP server.
	 *	@access		public
	 *	@param		Message		$message		Mail message object
	 *	@throws		RuntimeException			if message has no mail sender
	 *	@throws		RuntimeException			if message has no mail receivers
	 *	@throws		RuntimeException			if message has no mail body parts
	 *	@throws		RuntimeException			if connection to SMTP server failed
	 *	@throws		RuntimeException			if sending mail failed
	 *	@return		array<Result>				List of transport results
	 */
	public function send( Message $message ): array
	{
		if( NULL === $this->socket )
			$this->socket	= new SmtpSocket( $this->host, $this->port );
		$this->socket->setImplicitTls( $this->isSecure && $this->isImplicitTls, $this->cryptoMode );
		//  options of a socket given by setSocket() are kept, own options win; base taken once, so own options can be undone
		$this->socketSslOptions	??= $this->socket->getSslOptions();
		$this->socket->setSslOptions( array_merge( $this->socketSslOptions, $this->getSslOptions() ) );
		if( NULL !== $this->readTimeout )
			$this->socket->setReadTimeout( $this->readTimeout );
		if( NULL === $message->getSender() )
			throw new RuntimeException( 'No mail sender set' );
		if( !$message->hasRecipients() )													//  To may be empty, e.g. for Bcc only
			throw new RuntimeException( 'No mail receiver(s) set' );
		if( 0 === count( $message->getParts() ) )
			throw new RuntimeException( 'No mail body parts set' );
		$content	= MessageRenderer::render( $message );
		$response	= $this->socket->open();
		if( $response->isError() )
			throw new SmtpException( $response->getMessage(), $response->getError() );

		$heloHost	= $this->getHeloHost();
		$this->sendChunk( 'EHLO '.$heloHost );
		$this->checkResponse( [ 250 ], SmtpResponse::ERROR_HELO_FAILED );
		if( $this->isSecure && !$this->isImplicitTls ){
			$this->sendChunk( 'STARTTLS' );
			$this->checkResponse( [ 220 ], SmtpResponse::ERROR_CRYPTO_FAILED );
			$this->socket->enableCrypto( TRUE, $this->cryptoMode );
			//  the server discards what it knows about the client before TLS, so greet again (RFC 3207, 4.2)
			$this->sendChunk( 'EHLO '.$heloHost );
			$this->checkResponse( [ 250 ], SmtpResponse::ERROR_HELO_FAILED );
		}
		if( Str::hasContent( $this->username ) ){
			if( Str::hasContent( $this->password ) ){
				$this->sendChunk( 'AUTH LOGIN' );
				$this->checkResponse( [ 334 ] );
				$this->sendChunk( base64_encode( $this->username ), '[username hidden]' );
				$this->checkResponse( [ 334 ] );
				$this->sendChunk( base64_encode( $this->password ), '[password hidden]' );
				$this->checkResponse( [ 235 ], SmtpResponse::ERROR_LOGIN_FAILED );
			}
		}

		$list	= [];

		/** @var Address $sender */
		$sender		= $message->getSender();
		$this->sendChunk( 'MAIL FROM: <'.$sender->getAddress().'>' );
		$this->checkResponse( [ 250 ] );
		//  envelope gets all recipients, also CC and BCC, which are not or only partly in headers
		foreach( [ 'TO', 'CC', 'BCC' ] as $type ){
			/** @var Address $receiver */
			foreach( $message->getRecipientsByType( $type ) as $receiver ){
				$address	= strtolower( $receiver->getAddress() );
				if( isset( $list[$address] ) )
					continue;
				$result	= new Result();
				$result->setReceiver( $receiver );
				$this->sendChunk( 'RCPT TO: <'.$receiver->getAddress().'>' );
				//  251: not local, will forward (RFC 5321, 3.4); a rejected recipient does not stop sending to the others
				$response	= $this->checkResponse( [ 250, 251 ], SmtpResponse::ERROR_RECEIVER_NOT_ACCEPTED, FALSE );
				if( !( $response->isAccepted() ?? FALSE ) ){
					$result->setStatus( Result::STATUS_ERROR );
					$result->setCode( $response->getCode() );
					$result->setMessage( $response->getMessage() );
				}
				$list[$address]	= $result;
			}
		}
		$list		= array_values( $list );
		$accepted	= array_values( array_filter( $list, static fn( Result $result ) => Result::STATUS_ERROR !== $result->getStatus() ) );
		if( 0 === count( $accepted ) ){												//  no recipient accepted: no message to send
			$this->socket->close();														//  sends QUIT
			return $list;
		}
		$this->sendChunk( 'DATA' );
		$this->checkResponse( [ 354 ] );
		//  a line with a single dot ends the data, so leading dots are doubled (RFC 5321, 4.5.2)
		$this->sendChunk( Str::regReplace( '/^\./m', '..', $content, 'Doubling leading dots failed' ) );
		$this->sendChunk( '.' );
		try{
			$response	= $this->checkResponse( [ 250 ] );
			foreach( $accepted as $result ){
				$result->setStatus( Result::STATUS_SUCCESS );
				$result->setCode( $response->getCode() );
				$result->setMessage( $response->getMessage() );
			}
		}
		catch( SmtpException $e ){
			/** @var Response $response */
			$response	= $e->getResponse();
			foreach( $accepted as $result ){
				$result->setStatus( Result::STATUS_ERROR );
				$result->setCode( $response->getCode() );
				$result->setMessage( $response->getMessage() );
			}
		}
		catch( Throwable $t ){
			foreach( $accepted as $result ){
				$result->setStatus( Result::STATUS_FAILURE );
				$result->setCode( $t->getCode() );
				$result->setMessage( $t->getMessage() );
			}
		}
		$this->socket->close();

		return $list;
	}

	/**
	 *	Sets username and password for SMTP authentication.
	 *	Shortcut for setUsername and setPassword.
	 *	@access		public
	 *	@param		string		$username	SMTP username
	 *	@param		string		$password	SMTP password
	 *	@return		self  		Self instance for chaining.
	 */
	public function setAuth( string $username, string $password ): self
	{
		$this->setUsername( $username );
		$this->setPassword( $password );
		return $this;
	}

	/**
	 *	Sets crypto mode.
	 *	@access		public
	 *	@param		integer		$mode		Cryptography mode, default: STREAM_CRYPTO_METHOD_ANY_CLIENT
	 *	@return		self		Self instance for chaining.
	 *	@see		https://www.php.net/manual/en/function.stream-socket-enable-crypto.php
	 */
	public function setCryptoMode( int $mode ): self
	{
		$this->cryptoMode	= $mode;
		return $this;
	}

    /**
     *	Sets name of this client, sent with EHLO.
     *	Should be the fully qualified domain name of this host or an address literal like "[192.0.2.1]" (RFC 5321, 4.1.4).
     *	@access		public
     *	@param		string		$host		Name of this client
     *	@return		self		Self instance for chaining.
     */
    public function setHeloHost( string $host ): self
    {
        if( !Str::hasContent( $host ) )
            throw new InvalidArgumentException( 'Missing HELO host' );
        $this->heloHost	= trim( $host );
        return $this;
    }

    /**
     *	Sets kind of encryption, if secure: TLS right after connecting (SMTPS, usually port 465) or STARTTLS (usually port 587).
     *	@access		public
     *	@param		boolean		$implicit	Flag: implicit TLS instead of STARTTLS
     *	@return		self		Self instance for chaining.
     */
    public function setImplicitTls( bool $implicit ): self
    {
        $this->isImplicitTls	= $implicit;
        return $this;
    }

	/**
	 *	Sets SMTP host.
	 *	@access		public
	 *	@param		string		$host		SMTP server host
	 *	@return		self		Self instance for chaining.
	 */
	public function setHost( string $host ): self
	{
		if( NULL !== $this->socket )
			throw new RuntimeException( 'Connection to SMTP server already established' );
		if( !Str::hasContent( $host ) )
			throw new InvalidArgumentException( 'Missing SMTP host' );
		$this->host		= $host;
		return $this;
	}

	/**
	 *	Sets password for SMTP authentication.
	 *	@access		public
	 *	@param		string		$password	SMTP password
	 *	@return		self		Self instance for chaining.
	 */
	public function setPassword( string $password ): self
	{
		$this->password	= $password;
		return $this;
	}

	/**
	 *	Sets SMTP port.
	 *	@access		public
	 *	@param		integer		$port			SMTP server port
	 * 	@param		boolean		$detectSecure	Flag: set encryption depending on port (default: yes):
	 *											465: implicit TLS, 587: STARTTLS, other: none
	 *	@return		self		Self instance for chaining.
	 */
	public function setPort( int $port, bool $detectSecure = TRUE ): self
	{
		if( NULL !== $this->socket )
			throw new RuntimeException( 'Connection to SMTP server already established' );
		$this->port		= $port;
		if( $detectSecure ){
			$this->setSecure( in_array( $port, [ 465, 587 ], TRUE ), $this->validateCertificate );
			$this->setImplicitTls( 465 === $port );
		}
		return $this;
	}

	/**
	 *	Sets whether to encrypt the connection, see setImplicitTls() for the kind of encryption.
	 *	@access		public
	 *	@param		boolean		$secure					Flag: encrypt connection
	 *	@param		boolean		$validateCertificate	Flag: validate server certificate (default: yes), switch off for self-signed certificates only
	 *	@return		self		Self instance for chaining.
	 */
	public function setSecure( bool $secure, bool $validateCertificate = TRUE ): self
	{
		$this->isSecure				= $secure;
		$this->validateCertificate	= $validateCertificate;
		return $this;
	}

	/**
	 *	Sets timeout on waiting for a response of the server, e.g. a greeting or the answer to a command.
	 *	@access		public
	 *	@param		integer		$seconds	Timeout (in seconds), at least 1, default of socket: 30
	 *	@return		self		Self instance for chaining.
	 */
	public function setReadTimeout( int $seconds ): self
	{
		if( $seconds < 1 )
			throw new InvalidArgumentException( 'Read timeout must be at least 1 second' );
		$this->readTimeout	= $seconds;
		return $this;
	}

	/**
	 *	Sets SSL context options for TLS, e.g. a CA file to trust: ['cafile' => '/path/to/ca.pem'].
	 *	Options given here override the certificate validation flag of setSecure().
	 *	@access		public
	 *	@param		array<string,mixed>		$options	SSL context options
	 *	@return		self		Self instance for chaining.
	 *	@see		https://www.php.net/manual/en/context.ssl.php
	 */
	public function setSslOptions( array $options ): self
	{
		$this->sslOptions	= $options;
		return $this;
	}

	public function setSocket( SmtpSocket $socket ): self
	{
		$this->socket			= $socket;
		$this->socketSslOptions	= NULL;
		return $this;
	}

	/**
	 *	Sets username for SMTP authentication.
	 *	@access		public
	 *	@param		string		$username	SMTP username
	 *	@return		self		Self instance for chaining.
	 */
	public function setUsername( string $username ): self
	{
		$this->username	= $username;
		return $this;
	}

	/**
	 *	Sets verbosity.
	 *	@access		public
	 *	@param		boolean		$verbose	Be verbose during transportation (default: FALSE)
	 *	@return		self		Self instance for chaining.
	 */
	public function setVerbose( bool $verbose ): self
	{
		$this->verbose = $verbose;
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Returns name of this client for EHLO: set name, else the host name if fully qualified,
	 *	else the local IP address as address literal (RFC 5321, 4.1.3), else "localhost".
	 *	@access		protected
	 *	@return		string
	 */
	protected function getHeloHost(): string
	{
		if( NULL !== $this->heloHost )
			return $this->heloHost;
		$hostname	= gethostname();
		if( FALSE !== $hostname && str_contains( $hostname, '.' ) && FALSE !== filter_var( $hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) )
			return $hostname;
		$address	= $this->socket?->getLocalAddress();
		if( NULL !== $address ){
			$ip	= trim( substr( $address, 0, (int) strrpos( $address, ':' ) ), '[]' );
			if( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
				return '['.$ip.']';
			if( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
				return '[IPv6:'.$ip.']';
		}
		return 'localhost';
	}

	/**
	 *	Returns SSL context options for the socket: certificate validation by flag, overridden by set options.
	 *	@access		protected
	 *	@return		array<string,mixed>
	 */
	protected function getSslOptions(): array
	{
		$options	= [];
		if( !$this->validateCertificate )
			$options	= [
				'verify_peer'		=> FALSE,
				'verify_peer_name'	=> FALSE,
				'allow_self_signed'	=> TRUE,
			];
		return array_merge( $options, $this->sslOptions );
	}

	protected function checkResponse( array $acceptedCodes = [], ?int $errorCode = NULL, bool $strict = TRUE ): SmtpResponse
	{
		if( 0 === count( $acceptedCodes ) )
			throw new RangeException( 'No accepted codes set' );

		if( NULL === $this->socket )
			throw new RuntimeException( 'Not connected' );

		$response	= $this->socket->readResponse();
		if( $response->isError() ){
			$exception	= new SmtpException( $response->getMessage(), $response->getError() );
			$exception->setResponse( $response );
			throw $exception;
		}
		$response->setAcceptedCodes( $acceptedCodes );
		if( $this->verbose )
			print ' < ' . join( PHP_EOL . '   ', $response->getResponse() );

		if( FALSE === $response->isAccepted() ){
			if( NULL !== $errorCode )
				$response->setError( $errorCode );
			if( $strict ){
				$this->socket->close();
				$message	= vsprintf( 'Unexpected SMTP response (%s): %s', [
					$response->getCode(),
					$response->getMessage(),
				] );
				$exception	= new SmtpException( $message, $errorCode ?? 0 );
				$exception->setResponse( $response );
				throw $exception;
			}
		}
		return $response;
	}

	/**
	 *	Sends a command or content to the server.
	 *	@param		string			$message		Command or content to send
	 *	@param		string|NULL		$verboseMessage	Shown instead of message in verbose mode, e.g. to hide credentials
	 *	@return		bool
	 */
	protected function sendChunk( string $message, ?string $verboseMessage = NULL ): bool
	{
		if( NULL === $this->socket )
			throw new RuntimeException( 'Not connected' );

		if( $this->verbose )
			print PHP_EOL . ' > '.( $verboseMessage ?? $message ) . PHP_EOL;

		return $this->socket->sendChunk( $message.Message::$delimiter );
	}
}
