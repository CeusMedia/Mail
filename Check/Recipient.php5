<?php
class CMM_Mail_Check_Recipient{

	/**	@var	CMM_Mail_Participant	$sender		... */
	protected $sender;

	protected $lastResponse;

	const ERROR_NONE					= 0;
	const ERROR_MX_RESOLUTION_FAILED		 =1;
	const ERROR_SOCKET_FAILED			= 2;
	const ERROR_SOCKET_EXCEPTION		= 3;
	const ERROR_CONNECTION_FAILED		= 4;
	const ERROR_HELO_FAILED				= 5;
	const ERROR_SENDER_NOT_ACCEPTED		= 6;
	const ERROR_RECEIVER_NOT_ACCEPTED	= 7;

	public function __construct( CMM_Mail_Participant $sender, $verbose = FALSE ){
		$this->sender	= $sender;
		$this->verbose	= $verbose;
		$this->lastResponse	= (object) array(
			'error'		=> self::ERROR_NONE,
			'request'	=> NULL,
			'response'	=> NULL,
			'code'		=> NULL,
			'message'	=> NULL
		);
		$this->cache	= CMM_SEA_Factory::createStorage( 'Noop' );
	}

	public function setCache( CMM_SEA_Adapter $cache ){
		$this->cache	= $cache;
	}

	protected function getMailServers( $hostname ){
		if( $this->cache->has( 'mx:'.$hostname ) )
			return $this->cache->get( 'mx:'.$hostname );
		$servers	= array();
		getmxrr( $hostname, $mxRecords, $mxWeights );
		if( !$mxRecords )
			throw new RuntimeException( 'No MX records found for host: '.$hostname );
		foreach( $mxRecords as $nr => $server ){
			$servers[$mxWeights[$nr]]	= $server;
		}
		ksort( $servers );
		$this->cache->set( 'mx:'.$hostname, $servers );
		return $servers;
	}

	public function test( CMM_Mail_Participant $receiver, $host = NULL, $port = 25, $force = FALSE ){
		if( !$force ){
			if( $this->cache->has( 'user:'.$receiver->getAddress() ) ){
				return $this->cache->get( 'user:'.$receiver->getAddress() );
			}
		}

		if( !$host ){
			try{
				$servers	= $this->getMailServers( $receiver->getDomain() );
				$host		= array_shift( $servers );
			}
			catch( Exception $e ){
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
			$this->parseResponse( $conn );
			if( $this->lastResponse->code !== 220 ){
				$this->lastResponse->error	= self::ERROR_CONNECTION_FAILED;
				return FALSE;
			}
			$this->sendChunk( $conn, "HELO ".$this->sender->getDomain() );
			$this->parseResponse( $conn );
			if( $this->lastResponse->code !== 250 ){
				$this->lastResponse->error	= self::ERROR_HELO_FAILED;
				return FALSE;
			}
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
			$this->parseResponse( $conn );
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
			remark( ' > '.$this->lastResponse->response );
		$matches	= array();
		preg_match( '/^([0-9]{3}) (.+)$/', trim( $this->lastResponse->response ), $matches );
		if( !$matches )
			throw new RuntimeException( 'SMTP response not understood' );
		$this->lastResponse->code		= (int) $matches[1];
		$this->lastResponse->message	= $matches[2];
		return (int) $matches[1] < 400;
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
			return $this->lastResponse->error;
		}
	}

	protected function sendChunk( $connection, $message ){
		if( $this->verbose )
			remark( ' < '.$message );
		$this->lastResponse->request	= $message;
		fputs( $connection, $message.CMM_Mail_Message::$delimiter );
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
