<?php
/**
 *	Evaluate existence of mail receiver address.
 *
 *	Copyright (c) 2007-2016 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Check;
/**
 *	Evaluate existence of mail receiver address.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
class Recipient{

	/**	@var	CMM_Mail_Participant	$sender		... */
	protected $sender;
	protected $verbose;
	protected $lastResponse;

	const ERROR_NONE					= 0;
	const ERROR_MX_RESOLUTION_FAILED	= 1;
	const ERROR_SOCKET_FAILED			= 2;
	const ERROR_SOCKET_EXCEPTION		= 3;
	const ERROR_CONNECTION_FAILED		= 4;
	const ERROR_HELO_FAILED				= 5;
	const ERROR_SENDER_NOT_ACCEPTED		= 6;
	const ERROR_RECEIVER_NOT_ACCEPTED	= 7;

	public function __construct( $sender, $verbose = NULL ){
		if( is_string( $sender ) )
			$sender		= new \CeusMedia\Mail\Participant( $sender );
		$this->sender	= $sender;
		$this->setVerbose( $verbose );
		$this->lastResponse	= (object) array(
			'error'		=> self::ERROR_NONE,
			'request'	=> NULL,
			'response'	=> NULL,
			'code'		=> NULL,
			'message'	=> NULL
		);
		$this->cache	= \CeusMedia\Cache\Factory::createStorage( 'Noop' );
	}

	public function getLastError(){
		if( $this->lastResponse ){
			return (object) array(
				'error'		=> $this->lastResponse->error,
				'code'		=> $this->lastResponse->code,
				'message'	=> $this->lastResponse->message
			);
		}
	}

	public function getLastResponse(){
		if( $this->lastResponse ){
			return $this->lastResponse;
		}
	}

	protected function getMailServers( $hostname, $useCache = TRUE, $strict = TRUE ){
		if( $useCache && $this->cache->has( 'mx:'.$hostname ) )
			return $this->cache->get( 'mx:'.$hostname );
		$servers	= array();
		getmxrr( $hostname, $mxRecords, $mxWeights );
		if( !$mxRecords && $strict )
			throw new \RuntimeException( 'No MX records found for host: '.$hostname );
		foreach( $mxRecords as $nr => $server )
			$servers[$mxWeights[$nr]]	= $server;
		ksort( $servers );
		if( $useCache )
			$this->cache->set( 'mx:'.$hostname, $servers );
		return $servers;
	}

	public function test( $receiver, $host = NULL, $port = 25, $force = FALSE ){
		if( is_string( $receiver ) )
			$receiver	= new \CeusMedia\Mail\Participant( $receiver );
		if( !$force ){
			if( $this->cache->has( 'user:'.$receiver->getAddress() ) ){
				return $this->cache->get( 'user:'.$receiver->getAddress() );
			}
		}

		if( !$host ){
			try{
				$servers	= $this->getMailServers( $receiver->getDomain(), !$force, TRUE );
				$host		= array_shift( $servers );
			}
			catch( \Exception $e ){
				if( $force ){
					$this->lastResponse->error		= self::ERROR_MX_RESOLUTION_FAILED;
					$this->lastResponse->message	= $e->getMessage();
					return FALSE;
				}
				$host		= $receiver->getDomain();
			}
		}

		$conn	= @fsockopen( $host, $port, $errno, $errstr, 5 );
		if( !$conn ){
			$this->lastResponse->error		= self::ERROR_SOCKET_FAILED;
			$this->lastResponse->message	= 'Connection to server '.$host.':'.$port.' failed';
			return FALSE;
		}
		try{
			$this->parseResponse( $conn );
			if( (int) $this->lastResponse->code !== 220 ){
				$this->lastResponse->error	= self::ERROR_CONNECTION_FAILED;
				return FALSE;
			}
			$this->sendChunk( $conn, "HELO ".$this->sender->getDomain() );
			$this->parseResponse( $conn );
			if( !in_array( $this->lastResponse->code, array( 220, 250 ) ) ){
				$this->lastResponse->error	= self::ERROR_HELO_FAILED;
				return FALSE;
			}
			while( $this->lastResponse->code === 220 )									//  for telekom.de
				$this->parseResponse( $conn );
			$this->sendChunk( $conn, "MAIL FROM: <".$this->sender->getAddress().">" );
			$this->parseResponse( $conn );
			if( $this->lastResponse->code !== 250 ){
				$this->lastResponse->error	= self::ERROR_SENDER_NOT_ACCEPTED;
				return FALSE;
			}
			$this->sendChunk( $conn, "RCPT TO: <".$receiver->getAddress().">" );
			$this->parseResponse( $conn );
			if( $this->lastResponse->code !== 250 ){
				$this->lastResponse->error	= self::ERROR_RECEIVER_NOT_ACCEPTED;
				$this->cache->set( 'user:'.$receiver->getAddress(), FALSE );
				return FALSE;
			}
			$this->sendChunk( $conn, "QUIT" );
//			$this->parseResponse( $conn );
			fclose( $conn );
			$this->cache->set( 'user:'.$receiver->getAddress(), TRUE );
			return TRUE;
		}
		catch( Exception $e ){
			fclose( $conn );
			$this->lastResponse->error		= self::ERROR_SOCKET_EXCEPTION;
			$this->lastResponse->message	= $e->getMessage();
			return FALSE;
		}
	}

	protected function parseResponse( $connection ){
		$this->lastResponse->response	= fgets( $connection, 1024 );
		if( $this->verbose )
			print PHP_EOL.' < '.$this->lastResponse->response;
		$matches	= array();
		preg_match( '/^([0-9]{3})( |-)(.+)$/', trim( $this->lastResponse->response ), $matches );
		if( !$matches )
			throw new \RuntimeException( 'SMTP response not understood' );
		$this->lastResponse->code		= (int) $matches[1];
		$this->lastResponse->message	= $matches[3];
		return (int) $matches[1] < 400;
	}

	protected function sendChunk( $connection, $message ){
		if( $this->verbose )
			print PHP_EOL.' > '.$message;//htmlentities( $message), ENT_QUOTES, 'UTF-8' );
		$this->lastResponse->request	= $message;
		fputs( $connection, $message.\CeusMedia\Mail\Message::$delimiter );
	}

	public function setCache( \CeusMedia\Cache\AdapterAbstract $cache ){
		$this->cache	= $cache;
	}

	public function setVerbose( $verbose = TRUE ){
		$this->verbose	= (bool) $verbose;
	}
}
// support windows platforms
if( !function_exists( 'getmxrr' ) ){
	function getmxrr( $hostname, &$mxhosts, &$mxweight ){
		if( !is_array( $mxhosts ) ){
			$mxhosts	= array();
		}
		$pattern	= "/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.*)$/";
		if( !empty( $hostname ) ){
			$output	= "";
			@exec( "nslookup.exe -type=MX $hostname.", $output );
			$imx	= -1;
			foreach( $output as $line ){
				$imx++;
				$parts	= "";
				if( preg_match( $pattern, $line, $parts ) ){
					$mxweight[$imx]	= $parts[1];
					$mxhosts[$imx]	= $parts[2];
				}
			}
			return ($imx!=-1);
		}
		return FALSE;
	}
}
?>
