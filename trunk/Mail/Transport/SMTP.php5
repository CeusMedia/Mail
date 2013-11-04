<?php
/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	Copyright (c) 2010-2013 Christian Würker (ceusmedia.com)
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
 *	@category		cmModules
 *	@package		Mail.Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2013 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmclasses/
 *	@since			0.7.1
 *	@version		$Id: SMTP.php5 1112 2013-09-30 15:11:51Z christian.wuerker $
 */
/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	@category		cmModules
 *	@package		Mail.Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2013 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmclasses/
 *	@since			0.7.1
 *	@version		$Id: SMTP.php5 1112 2013-09-30 15:11:51Z christian.wuerker $
 *	@see			http://www.der-webdesigner.net/tutorials/php/anwendungen/329-php-und-oop-mailversand-via-smtp.html
 */
class CMM_Mail_Transport_SMTP
{
	/**	@var		string		$host		SMTP server host name */
	protected $host;
	/**	@var		integer		$port		SMTP server port */
	protected $port;
	/**	@var		string		$username	SMTP auth username */
	protected $username;
	/**	@var		string		$password	SMTP auth password */
	protected $password;

	protected $isSecure			= FALSE;

	protected $verbose			= FALSE;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$host		SMTP server host name
	 *	@param		integer		$port		SMTP server port
	 *	@param		string		$username	SMTP auth username
	 *	@param		string		$password	SMTP auth password
	 *	@return		void
	 */
	public function __construct( $host, $port = 25, $username = NULL, $password = NULL ){
		$this->host		= $host;
		$this->setPort( $port );
		$this->setUsername( $username );
		$this->setPassword( $password );
	}

	protected function checkResponse( $connection ){
		$response	= fgets( $connection, 1024 );
		if( $this->verbose )
			remark( ' > '.$response );
		$matches	= array();
		preg_match( '/^([0-9]{3}) (.+)$/', trim( $response ), $matches );
		if( $matches )
			if( (int) $matches[1] >= 400 )
				throw new RuntimeException( 'SMTP error: '.$matches[2], (int) $matches[1] );
	}

	/**
	 *	Sends mail using a socket connection to a remote SMTP server.
	 *	@access		public
	 *	@param		CMM_Mail_Message	$mail		Mail message object
	 *	@return		void
	 */
	public function send( CMM_Mail_Message $mail ){
		$delim	= CMM_Mail_Message::$delimiter;
		$server	= 'localhost';
		if( !empty( $_SERVER['SERVER_NAME'] ) )
			$server	= $_SERVER['SERVER_NAME'];
		$conn	= fsockopen( $this->host, $this->port, $errno, $errstr, 5 );
		if( !$conn )
			throw new RuntimeException( 'Connection to SMTP server "'.$this->host.':'.$this->port.'" failed' );
		if( !$mail->getSender() )
			throw new RuntimeException( 'No mail sender set' );
		if( !$mail->getReceivers() )
			throw new RuntimeException( 'No mail receiver(s) set' );
		if( !( $body = $mail->render() ) )
			throw new RuntimeException( 'No mail body set' );
		try{
			$this->checkResponse( $conn );
			$this->sendChunk( $conn, "HELO ".$server );
			$this->checkResponse( $conn );
			if( $this->isSecure ){
				$this->sendChunk( $conn, "STARTTLS" );
				$this->checkResponse( $conn );
			}
			if( $this->username && $this->password ){
				$this->sendChunk( $conn, "AUTH LOGIN" );
				$this->checkResponse( $conn );
				$this->sendChunk( $conn, base64_encode( $this->username ) );
				$this->checkResponse( $conn );
				$this->sendChunk( $conn, base64_encode( $this->password ) );
				$this->checkResponse( $conn );
			}
			$sender		= $mail->getSender();
			$address	= $sender->address;
			if( !empty( $sender->name ) )
				$address	= $sender->name.' <'.$address.'>';
			$this->sendChunk( $conn, "MAIL FROM: ".$address );
			foreach( $mail->getReceivers() as $receiver ){
				$address	= $receiver->address;
				if( !empty( $receiver->name ) )
					$address	= $receiver->name.' <'.$address.'>';
				$type	= empty( $receiver->type ) ? 'TO' : strtoupper( $receiver->type );
				$this->sendChunk( $conn, "RCPT ".$type.": ".$address );
			}

			$this->sendChunk( $conn, "DATA" );
			$this->checkResponse( $conn );
			$this->sendChunk( $conn, $body );
			$this->checkResponse( $conn );
			$this->sendChunk( $conn, '.' );
			$this->checkResponse( $conn );
			$this->checkResponse( $conn );
			$this->sendChunk( $conn, "QUIT" );
			$this->checkResponse( $conn );
			fclose( $conn );
		}
		catch( Exception $e ){
			fclose( $conn );
			throw new RuntimeException( $e->getMessage(), $e->getCode(), $e->getPrevious() );
		}
	}

	protected function sendChunk( $connection, $message ){
		if( $this->verbose )
			remark( ' < '.$message );
		fputs( $connection, $message.CMM_Mail_Message::$delimiter );
	}

	/**
	 *	Sets password for SMTP authentication.
	 *	@access		public
	 *	@param		string		$password	SMTP password
	 *	@return		void
	 */
	public function setPassword( $password ){
		$this->password	= $password;
		return $this;
	}

	/**
	 *	Sets SMTP port.
	 *	@access		public
	 *	@param		integer		$port		SMTP server port
	 *	@return		void
	 */
	public function setPort( $port )
	{
		$this->port		= $port;
		return $this;
	}

	public function setSecure( $secure ){
		$this->isSecure = (bool) $secure;
		return $this;
	}

	/**
	 *	Sets username for SMTP authentication.
	 *	@access		public
	 *	@param		string		$username	SMTP username
	 *	@return		void
	 */
	public function setUsername( $username ){
		$this->username	= $username;
		return $this;
	}

	public function setVerbose( $verbose ){
		$this->verbose = (bool) $verbose;
		return $this;
	}
}
?>
