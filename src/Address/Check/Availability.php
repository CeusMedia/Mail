<?php
/**
 *	Evaluate existence of mail receiver address.
 *
 *	Copyright (c) 2007-2020 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Check;

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Util\MX;

/**
 *	Evaluate existence of mail receiver address.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
class Availability
{
	/**	@var	\CeusMedia\Mail\Address	$sender		... */
	protected $sender;
	protected $verbose;
	protected $lastResponse;

	/** @var		\CeusMedia\Cache\AdapterAbstract */
	protected $cache;

	const ERROR_NONE					= 0;
	const ERROR_MX_RESOLUTION_FAILED	= 1;
	const ERROR_SOCKET_FAILED			= 2;
	const ERROR_SOCKET_EXCEPTION		= 3;
	const ERROR_CONNECTION_FAILED		= 4;
	const ERROR_HELO_FAILED				= 5;
	const ERROR_CRYPTO_FAILED			= 6;
	const ERROR_SENDER_NOT_ACCEPTED		= 7;
	const ERROR_RECEIVER_NOT_ACCEPTED	= 8;

	public function __construct( $sender, bool $verbose = NULL )
	{
		$this->setVerbose( (bool) $verbose );
		if( is_string( $sender ) )
			$sender		= new Address( $sender );
		$this->sender	= $sender;
		$this->lastResponse	= (object) array(
			'code'		=> 0,
			'error'		=> self::ERROR_NONE,
			'message'	=> NULL,
			'request'	=> NULL,
			'response'	=> NULL,
		);
		$this->cache	= \CeusMedia\Cache\Factory::createStorage( 'Noop' );
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		string		$key			Response data key (error|code|message)
	 *	@throws		\RangeException				if given key is invalid
	 *	@return		object|string|integer|NULL
	 */
	public function getLastError( ?string $key = NULL )
	{
		if( $this->lastResponse ){
			if( $key === NULL )
				return (object) array(
					'error'		=> $this->lastResponse->error,
					'code'		=> $this->lastResponse->code,
					'message'	=> $this->lastResponse->message
				);
			if( isset( $this->lastResponse->$key ) )
				return $this->lastResponse->$key;
			throw new \RangeException( 'Unknown key: '.$key );
		}
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		string		$key			Response data key (error|request|response|code|message)
	 *	@throws		\RangeException				if given key is invalid
	 *	@return		object|string|integer|NULL
	 */
	public function getLastResponse( ?string $key = NULL )
	{
		if( $this->lastResponse ){
			if( $key === NULL )
				return $this->lastResponse;
			if( isset( $this->lastResponse->$key ) )
				return $this->lastResponse->$key;
			throw new \RangeException( 'Unknown key: '.$key );
		}
	}

	protected function getMailServers( string $hostname, bool $useCache = TRUE, bool $strict = TRUE )
	{
		return MX::create()->fromHostname( $hostname, $useCache, $strict );
	}

	protected function readResponse( $connection, $acceptedCodes = array() )
	{
		$lastLine	= FALSE;
		$code		= NULL;
		$buffer		= array();
		do{
			$this->lastResponse->response	= fgets( $connection, 1024 );
			$this->lastResponse->error		= self::ERROR_NONE;
			$this->lastResponse->code		= 0;
			$this->lastResponse->message	= NULL;
			if( $this->verbose )
				print ' < '.$this->lastResponse->response;
			$matches	= array();
			preg_match( '/^([0-9]{3})( |-)(.+)$/', $this->lastResponse->response, $matches );
			if( !$matches )
				throw new \RuntimeException( 'SMTP response not understood' );
			$code		= (int) $matches[1];
			$buffer[]	= trim( $matches[3] );
			if( $acceptedCodes && !in_array( $code, $acceptedCodes ) )
				throw new \RuntimeException( 'Unexcepted SMTP response ('.$matches[1].'): '.$matches[3], $code );
			if( $matches[2] === " " )
				$lastLine	= TRUE;
			$this->lastResponse->code		= (int) $matches[1];
			$this->lastResponse->message	= trim( $matches[3] );
		}
		while( $this->lastResponse->response && !$lastLine );
		return (object) array(
			'code'		=> $code,
			'message'	=> join( "\n", $buffer ),
		);
	}

	public function test( $receiver, ?string $host = NULL, ?int $port = 587, ?bool $force = FALSE )
	{
		if( is_string( $receiver ) )
			$receiver	= new Address( $receiver );
		if( !$force ){
			if( $this->cache->has( 'user:'.$receiver->getAddress() ) ){
				return $this->cache->get( 'user:'.$receiver->getAddress() );
			}
		}

		$this->lastResponse->error		= self::ERROR_NONE;
		$this->lastResponse->code		= 0;
		$this->lastResponse->message	= NULL;
		$this->lastResponse->response	= NULL;
		$this->lastResponse->request	= NULL;

		if( !$host ){
			try{
				$servers	= $this->getMailServers( $receiver->getDomain(), !$force, TRUE );
				$host		= array_shift( $servers );
			}
			catch( \Exception $e ){
				$this->lastResponse->error		= self::ERROR_MX_RESOLUTION_FAILED;
				$this->lastResponse->message	= $e->getMessage();
				return FALSE;
			}
		}


		$conn	= @fsockopen( $host, $port, $errno, $errstr, 5 );
		if( !$conn ){
			$this->lastResponse->error		= self::ERROR_SOCKET_FAILED;
			$this->lastResponse->message	= 'Connection to server '.$host.':'.$port.' failed';
			return FALSE;
		}
		try{
			$this->readResponse( $conn );
			if( $this->lastResponse->code !== 220 ){
				$this->lastResponse->error	= self::ERROR_CONNECTION_FAILED;
				return FALSE;
			}
			$this->sendChunk( $conn, "EHLO ".$this->sender->getDomain() );
			$this->readResponse( $conn );
			if( !in_array( $this->lastResponse->code, array( 220, 250 ) ) ){
				$this->lastResponse->error	= self::ERROR_HELO_FAILED;
				return FALSE;
			}
			$this->sendChunk( $conn, "STARTTLS" );
			$this->readResponse( $conn );
			if( $this->lastResponse->code !== 220 ){
				$this->lastResponse->error	= self::ERROR_CRYPTO_FAILED;
				return FALSE;
			}
			stream_socket_enable_crypto( $conn, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT );

//			while( $this->lastResponse->code === 220 )									//  for telekom.de
//				$this->readResponse( $conn );
			$this->sendChunk( $conn, "MAIL FROM: <".$this->sender->getAddress().">" );
			$this->readResponse( $conn );
			if( $this->lastResponse->code !== 250 ){
				$this->lastResponse->error	= self::ERROR_SENDER_NOT_ACCEPTED;
				return FALSE;
			}
			$this->sendChunk( $conn, "RCPT TO: <".$receiver->getAddress().">" );
			$this->readResponse( $conn );
			if( $this->lastResponse->code !== 250 ){
				$this->lastResponse->error	= self::ERROR_RECEIVER_NOT_ACCEPTED;
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
			$this->lastResponse->error		= self::ERROR_SOCKET_EXCEPTION;
			$this->lastResponse->message	= $e->getMessage();
			return FALSE;
		}
	}

	protected function sendChunk( $connection, string $message )
	{
		if( $this->verbose )
			print ' > '.$message.PHP_EOL;//htmlentities( $message), ENT_QUOTES, 'UTF-8' );
		$this->lastResponse->request	= $message;
		fputs( $connection, $message.Message::$delimiter );
	}

	public function setCache( \CeusMedia\Cache\AdapterAbstract $cache ): self
	{
		$this->cache	= $cache;
		return $this;
	}

	public function setVerbose( ?bool $verbose = TRUE ): self
	{
		$this->verbose	= $verbose;
		return $this;
	}
}
