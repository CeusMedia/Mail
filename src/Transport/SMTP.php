<?php
declare(strict_types=1);

/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	Copyright (c) 2007-2024 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer as MessageRenderer;
use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\Mail\Transport\SMTP\Response as SmtpResponse;
use CeusMedia\Mail\Transport\SMTP\Socket as SmtpSocket;
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;

use InvalidArgumentException;
use RangeException;
use RuntimeException;

use Throwable;
use function count;
use function in_array;
use function join;
use function strlen;

/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://www.der-webdesigner.net/tutorials/php/anwendungen/329-php-und-oop-mailversand-via-smtp.html
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

	/**	@var	boolean				$isSecure */
	protected bool $isSecure		= FALSE;

	/**	@var	boolean				$verbose */
	protected bool $verbose			= FALSE;

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
			if( 0 !== strlen( trim( $socketError ) ) )
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
		if( NULL === $message->getSender() )
			throw new RuntimeException( 'No mail sender set' );
		if( 0 === count( $message->getRecipientsByType( 'to' ) ) )
			throw new RuntimeException( 'No mail receiver(s) set' );
		if( 0 === count( $message->getParts() ) )
			throw new RuntimeException( 'No mail body parts set' );
		$content	= MessageRenderer::render( $message );
		$response	= $this->socket->open();
		if( $response->isError() )
			throw new SmtpException( $response->getMessage(), $response->getError() );

		$this->sendChunk( 'EHLO '.$this->host );
		$this->checkResponse( [ 250 ], SmtpResponse::ERROR_HELO_FAILED );
		if( $this->isSecure ){
			$this->sendChunk( 'STARTTLS' );
			$this->checkResponse( [ 220 ], SmtpResponse::ERROR_CRYPTO_FAILED );
			$this->socket->enableCrypto( TRUE, $this->cryptoMode );
		}
		if( 0 < strlen( trim( $this->username ) ) ){
			if( 0 < strlen( trim( $this->password ) ) ){
				$this->sendChunk( 'AUTH LOGIN' );
				$this->checkResponse( [ 334 ] );
				$this->sendChunk( base64_encode( $this->username ) );
				$this->checkResponse( [ 334 ] );
				$this->sendChunk( base64_encode( $this->password ) );
				$this->checkResponse( [ 235 ], SmtpResponse::ERROR_LOGIN_FAILED );
			}
		}

		$list	= [];

		/** @var Address $sender */
		$sender		= $message->getSender();
		$this->sendChunk( 'MAIL FROM: <'.$sender->getAddress().'>' );
		$this->checkResponse( [ 250 ] );
		/** @var Address $receiver */
		foreach( $message->getRecipientsByType( 'TO' )->filter() as $receiver ){
			$result	= new Result();
			$result->setReceiver( $receiver );
			$this->sendChunk( 'RCPT TO: <'.$receiver->getAddress().'>' );
			$this->checkResponse( [ 250 ] );
			$list[]	= $result;
		}
		$this->sendChunk( 'DATA' );
		$this->checkResponse( [ 354 ] );
		$this->sendChunk( $content );
		$this->sendChunk( '.' );
		try{
			$response	= $this->checkResponse( [ 250 ] );
			foreach( $list as $result ){
				$result->setStatus( Result::STATUS_SUCCESS );
				$result->setCode( $response->getCode() );
				$result->setMessage( $response->getMessage() );
			}
		}
		catch( SmtpException $e ){
			/** @var Response $response */
			$response	= $e->getResponse();
			foreach( $list as $result ){
				$result->setStatus( Result::STATUS_ERROR );
				$result->setCode( $response->getCode() );
				$result->setMessage( $response->getMessage() );
			}
		}
		catch( Throwable $t ){
			foreach( $list as $result ){
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
	 *	Sets SMTP host.
	 *	@access		public
	 *	@param		string		$host		SMTP server host
	 *	@return		self		Self instance for chaining.
	 */
	public function setHost( string $host ): self
	{
		if( NULL !== $this->socket )
			throw new RuntimeException( 'Connection to SMTP server already established' );
		if( 0 === strlen( trim( $host ) ) )
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
	 * 	@param		boolean		$detectSecure	Flag: set secure depending on port (default: yes)
	 *	@return		self		Self instance for chaining.
	 */
	public function setPort( int $port, bool $detectSecure = TRUE ): self
	{
		if( NULL !== $this->socket )
			throw new RuntimeException( 'Connection to SMTP server already established' );
		$this->port		= $port;
		if( $detectSecure )
			$this->setSecure( in_array( $port, [ 465, 587 ], TRUE ) );
		return $this;
	}

	public function setSecure( bool $secure ): self
	{
		$this->isSecure = $secure;
		return $this;
	}

	public function setSocket( SmtpSocket $socket ): self
	{
		$this->socket	= $socket;
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

	protected function checkResponse( array $acceptedCodes = [], ?int $errorCode = NULL, bool $strict = TRUE ): SmtpResponse
	{
		if( 0 === count( $acceptedCodes ) )
			throw new RangeException( 'No accepted codes set' );

		/** @phpstan-ignore-next-line */
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
				/** @phpstan-ignore-next-line */
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

	protected function sendChunk( string $message ): bool
	{
		if( $this->verbose )
			print PHP_EOL . ' > '.$message . PHP_EOL;
		/** @phpstan-ignore-next-line */
		return $this->socket->sendChunk( $message.Message::$delimiter );
	}
}
