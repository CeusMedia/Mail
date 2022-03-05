<?php
declare(strict_types=1);

/**
 *	Evaluate existence of mail receiver address.
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
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Check;

use CeusMedia\Cache\AdapterInterface as CacheAdapterInterface;
use CeusMedia\Cache\Factory as CacheFactory;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP\Socket as SmtpSocket;
use CeusMedia\Mail\Transport\SMTP\Response as SmtpResponse;
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;
use CeusMedia\Mail\Util\MX;

use Exception;
use RangeException;
use RuntimeException;

use function array_key_exists;
use function array_shift;
use function count;
use function fclose;
use function fgets;
use function fputs;
use function fsockopen;
use function in_array;
use function is_string;
use function join;
use function preg_match;
use function stream_socket_enable_crypto;
use function trim;

/**
 *	Evaluate existence of mail receiver address.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
class Availability
{
	/**	@var    Address			$sender		... */
	protected $sender;

	/**	@var    bool			$verbose		... */
	protected $verbose;

	/** @var	SmtpResponse	$lastResponse */
	protected $lastResponse;

	/** @var	CacheAdapterInterface	$cache */
	protected $cache;

	/**
	 *	Availability constructor.
	 *	@param		Address|string		$sender
	 *	@param		bool				$verbose
	 */
	public function __construct( $sender, bool $verbose = FALSE )
	{
		if( is_string( $sender ) )
			$sender		= new Address( $sender );
		$this->sender	= $sender;
		$this->setVerbose( $verbose );

		$this->cache		= CacheFactory::createStorage( 'Noop' );
		$this->lastResponse	= new SmtpResponse();
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		string|NULL			$key			Response data key (error|code|message)
	 *	@throws		RangeException						if given key is invalid
	 *	@return		object|string|integer|NULL
	 *	@deprecated	use getLastResponse instead
	 *	@codeCoverageIgnore
	 */
	public function getLastError( ?string $key = NULL )
	{
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method getLastResponse instead' );
		return $this->getLastResponse( $key );
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		string|NULL			$key			Response data key (error|request|response|code|message)
	 *	@throws		RangeException						if given key is invalid
	 *	@return		object|string|integer|NULL
	 */
	public function getLastResponse( ?string $key = NULL )
	{
		$properties = get_object_vars( $this->lastResponse );
		if( NULL !== $key ){
			if( array_key_exists( $key, $properties ) )
				return $properties[$key];
			throw new RangeException( 'Unknown key: '.$key );
		}
		return (object) $properties;
	}

	/**
	 * @param Address|string $receiver
	 * @param string|null $host
	 * @param int $port
	 * @param bool $force
	 * @return bool
	 */
	public function test( $receiver, string $host = NULL, int $port = 587, bool $force = FALSE ): bool
	{
		if( is_string( $receiver ) )
			$receiver	= new Address( $receiver );
		if( !$force ){
			if( $this->cache->has( 'user:'.$receiver->getAddress() ) ){
				return $this->cache->get( 'user:'.$receiver->getAddress() );
			}
		}

		$this->lastResponse	= new SmtpResponse();

		if( NULL === $host || 0 === strlen( $host ) ){
			try{
				$servers	= $this->getMailServers( $receiver->getDomain(), !$force, TRUE );
				$host		= array_shift( $servers );
			}
			catch( Exception $e ){
				$this->lastResponse->setError( SmtpResponse::ERROR_MX_RESOLUTION_FAILED );
				$this->lastResponse->setMessage( $e->getMessage() );
				return FALSE;
			}
		}

		$socket		= SmtpSocket::getInstance( $host, $port, 5 );
		try{
			$this->lastResponse	= $socket->open();
			if( SmtpResponse::ERROR_NONE !== $this->lastResponse->getError() )
				return $this->quit( $socket, FALSE );

			$request	= "EHLO ".$this->sender->getDomain();
			$response	= $this->request( $socket, $request, [ 220, 250 ], SmtpResponse::ERROR_HELO_FAILED );
			if( $response->isError() )
				return $this->quit( $socket, FALSE );
			$features	= $response->getResponse();
			$targetHost	= array_shift( $features );
			if( in_array( 'VRFY', $features, TRUE ) ){
				$request	= "VRFY ".$receiver->getAddress();
				$response	= $this->request( $socket, $request );
				$success	= in_array( $response->getCode(), [ 250, 251, 252 ], TRUE );
				$this->cache->set( 'user:'.$receiver->getAddress(), TRUE );
				return $this->quit( $socket, $success );
			}
			else{
				$response	= $this->request( $socket, "STARTTLS", [ 220 ], SmtpResponse::ERROR_CRYPTO_FAILED );
				if( $response->isError() )
					return $this->quit( $socket, FALSE );
				$socket->enableCrypto( TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT );

//				while( $response->getCode() === 220 )									//  for telekom.de
//					$this->readResponse( $conn );

				$request	= "MAIL FROM: <".$this->sender->getAddress().">";
				$response	= $this->request( $socket, $request, [ 250 ], SmtpResponse::ERROR_SENDER_NOT_ACCEPTED );
				if( $response->isError() )
					return $this->quit( $socket, FALSE );
				$request	= "RCPT TO: <".$receiver->getAddress().">";
				$response	= $this->request( $socket, $request, [ 250 ], SmtpResponse::ERROR_RECEIVER_NOT_ACCEPTED );
				if( $response->isError() ){
					$this->cache->set( 'user:'.$receiver->getAddress(), FALSE );
					return $this->quit( $socket, FALSE );
				}
				$this->cache->set( 'user:'.$receiver->getAddress(), TRUE );
				return $this->quit( $socket, TRUE );
			}
		}
		catch( Exception $e ){
			$this->lastResponse->setError( SmtpResponse::ERROR_UNSPECIFIED );
			$this->lastResponse->setMessage( $e->getMessage() );
			return $this->quit( $socket, FALSE );
		}
	}

	public function setCache( CacheAdapterInterface $cache ): self
	{
		$this->cache	= $cache;
		return $this;
	}

	/**
	 *	@access		public
	 *	@param		boolean		$verbose		Flag: enable or disable verbosity
	 *	@return		self
	 */
	public function setVerbose( bool $verbose = TRUE ): self
	{
		$this->verbose	= $verbose;
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	@access		protected
	 *	@param		string		$hostname
	 *	@param		boolean		$useCache
	 *	@param		boolean		$strict
	 *	@return		array
	 */
	protected function getMailServers( string $hostname, bool $useCache = TRUE, bool $strict = TRUE ): array
	{
		return MX::getInstance()->fromHostname( $hostname, $useCache, $strict );
	}

	/**
	 *	Closes socket connection if open and returns given return status.
	 *	@param		SmtpSocket	$socket			Possibly connected SMTP Socket
	 *	@param		boolean		$withSuccess	Return status to reflect
	 *	@return		boolean
	 */
	protected function quit( SmtpSocket $socket, bool $withSuccess = FALSE ): bool
	{
		$socket->close();
		return $withSuccess;
	}

	/**
	 *	Sends request message and receives response.
	 *	@param		SmtpSocket	$socket			Connected SMTP Socket
	 *	@param		string		$request		Request message to send
	 *	@return		SmtpResponse
	 */
	protected function request( SmtpSocket $socket, string $request, array $acceptedCodes = [], ?int $errorCode = NULL ): SmtpResponse
	{
		if( $this->verbose )
			print ' > '.$request.PHP_EOL;//htmlentities( $request, ENT_QUOTES, 'UTF-8' );
		$socket->sendChunk( $request.Message::$delimiter );

		$response	= $socket->readResponse();
		$response->setRequest( $request );

		if( $this->verbose )
			print ' < ' . join( PHP_EOL . '   ', $response->getResponse() ) . PHP_EOL;
		if( 0 !== count( $acceptedCodes ) ){
			$response->setAcceptedCodes( $acceptedCodes );
			if( !in_array( $response->getCode(), $acceptedCodes, TRUE ) ){
				if( NULL !== $errorCode )
					$response->setError( $errorCode );
				else{
					$message	= vsprintf( 'Unexcepted SMTP response (%s): %s', [
						$response->getCode(),
						$response->message
					] );
					$exception	= new SmtpException( $message );
					$exception->setResponse( $response );
					throw $exception;
				}
			}
		}

		$this->lastResponse = $response;
		return $response;
	}
}
