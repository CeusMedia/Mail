<?php
/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
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
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport;
/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://www.der-webdesigner.net/tutorials/php/anwendungen/329-php-und-oop-mailversand-via-smtp.html
 */
class SMTP{
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
		$this->setHost( $host );
		$this->setPort( $port );
		$this->setSecure( in_array( $port, array( 465, 587 ) ) );
		$this->setUsername( $username );
		$this->setPassword( $password );
	}

	protected function checkResponse( $connection, $acceptedCodes = array() ){
		$lastLine	= FALSE;
		$code		= NULL;
		$buffer		= array();
		do{
			$response	= fgets( $connection, 1024 );
			if( $this->verbose )
				print ' < '.$response;
			$matches	= array();
			preg_match( '/^([0-9]{3})( |-)(.+)$/', trim( $response ), $matches );
			if( !$matches )
				throw new \RuntimeException( 'SMTP response not understood: '.$lastLine );
			$code		= (int) $matches[1];
			$buffer[]	= $matches[3];
			if( $acceptedCodes && !in_array( $code, $acceptedCodes ) )
				throw new \RuntimeException( 'Unexcepted SMTP response ('.$matches[1].'): '.$matches[3], $code );
			if( $matches[2] === " " )
				$lastLine	= TRUE;
		}
		while( $response && !$lastLine );
		return (object) array(
			'code'		=> $code,
			'message'	=> join( "\n", $buffer ),
		);
	}

	static public function getInstance( $host, $port = 25 ){
		return new self( $host, $port );
	}

	/**
	 *	Sends mail using a socket connection to a remote SMTP server.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Message	$message		Mail message object
	 *	@throws		\RuntimeException		if message has no mail sender
	 *	@throws		\RuntimeException		if message has no mail receivers
	 *	@throws		\RuntimeException		if message has no mail body parts
	 *	@throws		\RuntimeException		if connection to SMTP server failed
	 *	@throws		\RuntimeException		if sending mail failed
	 *	@return		object  	Self instance for chaining.
	 */
	public function send( \CeusMedia\Mail\Message $message ){
		$delim		= \CeusMedia\Mail\Message::$delimiter;
		if( !$message->getSender() )
			throw new \RuntimeException( 'No mail sender set' );
		if( !$message->getRecipients( 'to') )
			throw new \RuntimeException( 'No mail receiver(s) set' );
		if( !$message->getParts() )
			throw new \RuntimeException( 'No mail body parts set' );
		$content	= \CeusMedia\Mail\Message\Renderer::render( $message );

		$conn	= fsockopen( $this->host, $this->port, $errno, $errstr, 5 );
		if( !$conn )
			throw new \RuntimeException( 'Connection to SMTP server "'.$this->host.':'.$this->port.'" failed' );
		try{
			$this->checkResponse( $conn, array( 220 ) );
			$this->sendChunk( $conn, "EHLO ".$this->host );
			$this->checkResponse( $conn, array( 250 ) );
			if( $this->isSecure ){
				$this->sendChunk( $conn, "STARTTLS" );
				$this->checkResponse( $conn, array( 220 ) );
				stream_socket_enable_crypto( $conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT );
			}
			if( $this->username && $this->password ){
				$this->sendChunk( $conn, "AUTH LOGIN" );
				$this->checkResponse( $conn, array( 334 ) );
				$this->sendChunk( $conn, base64_encode( $this->username ) );
				$this->checkResponse( $conn, array( 334 ) );
				$this->sendChunk( $conn, base64_encode( $this->password ) );
				$this->checkResponse( $conn, array( 235 ) );
			}
			$sender		= $message->getSender();
			$this->sendChunk( $conn, "MAIL FROM: <".$sender->getAddress().">" );
			$this->checkResponse( $conn, array( 250 ) );
			foreach( $message->getRecipients( 'to' ) as $receiver ){
				$this->sendChunk( $conn, "RCPT TO: <".$receiver->getAddress().">" );
				$this->checkResponse( $conn, array( 250 ) );
			}
			$this->sendChunk( $conn, "DATA" );
			$this->checkResponse( $conn, array( 354 ) );
			$this->sendChunk( $conn, $content );
			$this->sendChunk( $conn, '.' );
			$this->checkResponse( $conn, array( 250 ) );
			$this->sendChunk( $conn, "QUIT" );
			$this->checkResponse( $conn, array( 221 ) );
			fclose( $conn );
		}
		catch( \Exception $e ){
			fclose( $conn );
			throw new \RuntimeException( $e->getMessage(), $e->getCode(), $e->getPrevious() );
		}
		return $this;
	}

	protected function sendChunk( $connection, $message ){
		if( $this->verbose )
			print PHP_EOL . ' > '.$message . PHP_EOL;
		fputs( $connection, $message.\CeusMedia\Mail\Message::$delimiter );
	}

	/**
	 *	Sets username and password for SMTP authentication.
	 *	Shortcut for setUsername and setPassword.
	 *	@access		public
	 *	@param		string		$username	SMTP username
	 *	@param		string		$password	SMTP password
	 *	@return		object  	Self instance for chaining.
	 */
	public function setAuth( $username, $password ){
		$this->setUsername( $username );
		$this->setPassword( $password );
		return $this;
	}

	/**
	 *	Sets SMTP host.
	 *	@access		public
	 *	@param		string		$host		SMTP server host
	 *	@return		object  	Self instance for chaining.
	 */
	public function setHost( $host ){
		if( !strlen( trim( $host ) ) )
			throw new \InvalidArgumentException( 'Missing SMTP host' );
		$this->host		= $host;
		return $this;
	}

	/**
	 *	Sets password for SMTP authentication.
	 *	@access		public
	 *	@param		string		$password	SMTP password
	 *	@return		object  	Self instance for chaining.
	 */
	public function setPassword( $password ){
		$this->password	= $password;
		return $this;
	}

	/**
	 *	Sets SMTP port.
	 *	@access		public
	 *	@param		integer		$port		SMTP server port
	 *	@return		object  	Self instance for chaining.
	 */
	public function setPort( $port ){
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
	 *	@return		object  	Self instance for chaining.
	 */
	public function setUsername( $username ){
		$this->username	= $username;
		return $this;
	}

	/**
	 *	Sets verbosity.
	 *	@access		public
	 *	@param		boolean		$verbose	Be verbose during transportation (default: FALSE)
	 *	@return		object  	Self instance for chaining.
	 */
	public function setVerbose( $verbose ){
		$this->verbose = (bool) $verbose;
		return $this;
	}
}
?>
