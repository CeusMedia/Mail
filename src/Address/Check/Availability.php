<?php
declare(strict_types=1);

/**
 *	Evaluate existence of mail receiver address.
 *
 *	Copyright (c) 2007-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Check;

use CeusMedia\Cache\AdapterInterface as CacheAdapter;
use CeusMedia\Cache\Factory as CacheFactory;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP\Response as SmtpResponse;
use CeusMedia\Mail\Util\MX;

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
 *	@copyright		2007-2021 Christian Würker
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

	/** @var	CacheAdapter	$cache */
	protected $cache;

	/**
	 *	Availability constructor.
	 *	@param		Address|string		$sender
	 *	@param		bool				$verbose
	 */
	public function __construct( $sender, bool $verbose = FALSE )
	{
		$this->setVerbose( $verbose );
		if( is_string( $sender ) )
			$sender		= new Address( $sender );
		$this->sender	= $sender;
		$this->lastResponse	= new SmtpResponse();
		$this->cache	= CacheFactory::createStorage( 'Noop' );
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		string|NULL			$key			Response data key (error|code|message)
	 *	@throws		RangeException						if given key is invalid
	 *	@return		object|string|integer|NULL
	 *	@deprecated	use getLastResponse instead
	 */
	public function getLastError( ?string $key = NULL )
	{
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

		$this->lastResponse->setError( SmtpResponse::ERROR_NONE );
		$this->lastResponse->setCode( 0 );
		$this->lastResponse->setMessage();
		$this->lastResponse->setResponse();
		$this->lastResponse->setRequest();

		if( NULL === $host || 0 === strlen( $host ) ){
			try{
				$servers	= $this->getMailServers( $receiver->getDomain(), !$force, TRUE );
				$host		= array_shift( $servers );
			}
			catch( \Exception $e ){
				$this->lastResponse->setError( SmtpResponse::ERROR_MX_RESOLUTION_FAILED );
				$this->lastResponse->setMessage( $e->getMessage() );
				return FALSE;
			}
		}

		$conn	= @fsockopen( $host, $port, $errno, $errstr, 5 );
		if( FALSE === $conn ){
			$this->lastResponse->setError( SmtpResponse::ERROR_SOCKET_FAILED );
			$this->lastResponse->setMessage( 'Connection to server '.$host.':'.$port.' failed' );
			return FALSE;
		}
		try{
			$this->readResponse( $conn );
			if( 220 !== $this->lastResponse->getCode() ){
				$this->lastResponse->setError( SmtpResponse::ERROR_CONNECTION_FAILED );
				return FALSE;
			}
			$this->sendChunk( $conn, "EHLO ".$this->sender->getDomain() );
			$this->readResponse( $conn );
			if( !in_array( $this->lastResponse->getCode(), [ 220, 250 ], TRUE ) ){
				$this->lastResponse->setError( SmtpResponse::ERROR_HELO_FAILED );
				return FALSE;
			}
			$this->sendChunk( $conn, "STARTTLS" );
			$this->readResponse( $conn );
			if( 220 !== $this->lastResponse->getCode() ){
				$this->lastResponse->setError( SmtpResponse::ERROR_CRYPTO_FAILED );
				return FALSE;
			}
			stream_socket_enable_crypto( $conn, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT );

//			while( $this->lastResponse->getCode() === 220 )									//  for telekom.de
//				$this->readResponse( $conn );
			$this->sendChunk( $conn, "MAIL FROM: <".$this->sender->getAddress().">" );
			$this->readResponse( $conn );
			if( 250 !== $this->lastResponse->getCode() ){
				$this->lastResponse->setError( SmtpResponse::ERROR_SENDER_NOT_ACCEPTED );
				return FALSE;
			}
			$this->sendChunk( $conn, "RCPT TO: <".$receiver->getAddress().">" );
			$this->readResponse( $conn );
			if( 250 !== $this->lastResponse->getCode() ){
				$this->lastResponse->setError( SmtpResponse::ERROR_RECEIVER_NOT_ACCEPTED );
				$this->cache->set( 'user:'.$receiver->getAddress(), FALSE );
				return FALSE;
			}
			$this->sendChunk( $conn, "QUIT" );
			fclose( $conn );
			$this->cache->set( 'user:'.$receiver->getAddress(), TRUE );
			return TRUE;
		}
		catch( \Exception $e ){
			fclose( $conn );
			$this->lastResponse->setError( SmtpResponse::ERROR_SOCKET_EXCEPTION );
			$this->lastResponse->setMessage( $e->getMessage() );
			return FALSE;
		}
	}

	public function setCache( CacheAdapter $cache ): self
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

	protected function getMailServers( string $hostname, bool $useCache = TRUE, bool $strict = TRUE ): array
	{
		return MX::getInstance()->fromHostname( $hostname, $useCache, $strict );
	}

	/**
	 * @param resource $connection
	 * @param array $acceptedCodes
	 * @return object
	 */
	protected function readResponse( $connection, array $acceptedCodes = [] ): object
	{
		$lastLine	= FALSE;
		$code		= NULL;
		$buffer		= [];
		do{
			$response	= fgets( $connection, 1024 );
			if( FALSE !== $response ) {
				$this->lastResponse->setResponse( $response );
				$this->lastResponse->setError(SmtpResponse::ERROR_NONE);
				$this->lastResponse->setCode(0);
				$this->lastResponse->setMessage();
				if( $this->verbose )
					print ' < ' . $response;
				$matches = [];
				preg_match('/^([0-9]{3})( |-)(.+)$/', $response, $matches);
				if( !$matches )
					throw new RuntimeException('SMTP response not understood');
				$code = (int) $matches[1];
				$buffer[] = trim( $matches[3] );
				if( 0 < count($acceptedCodes) && !in_array($code, $acceptedCodes, TRUE ) )
					throw new RuntimeException('Unexcepted SMTP response (' . $matches[1] . '): ' . $matches[3], $code );
				if( ' ' === $matches[2])
					$lastLine = TRUE;
				$this->lastResponse->setCode( (int) $matches[1] );
				$this->lastResponse->setMessage( trim( $matches[3] ) );
			}
		}
		while( FALSE !== $response && !$lastLine );
		return (object) [
			'code'		=> $code,
			'message'	=> join( "\n", $buffer ),
		];
	}

	/**
	 *	...
	 *	@param		resource		$connection
	 *	@param		string			$message
	 *	@return		bool
	 */
	protected function sendChunk( $connection, string $message ): bool
	{
		if( $this->verbose )
			print ' > '.$message.PHP_EOL;//htmlentities( $message), ENT_QUOTES, 'UTF-8' );
		$this->lastResponse->setRequest( $message );
		return FALSE !== fputs( $connection, $message.Message::$delimiter );
	}
}
