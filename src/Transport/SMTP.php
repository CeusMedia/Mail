<?php
/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	Copyright (c) 2007-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Renderer as MessageRenderer;
use \CeusMedia\Mail\Transport\SMTP\Socket as SmtpSocket;

/**
 *	Sends Mail using a remote SMTP Server and a Socket Connection.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
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

	protected $socket;

	/**
	 *	Constructor.
	 *	Receives connection parameters (host, port, username, passord) if given.
	 *
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
		$this->setUsername( $username );
		$this->setPassword( $password );
		$this->socket	= new SmtpSocket( $this->host, (int) $this->port );
	}

	protected function checkResponse( $acceptedCodes = array() ){
		$response	= $this->socket->readResponse( 1024 );
		if( $this->verbose )
			print ' < '.join( PHP_EOL."   ", $response->raw );
		if( $acceptedCodes && !in_array( $response->code, $acceptedCodes ) )
			throw new \RuntimeException( 'Unexcepted SMTP response ('.$response->code.'): '.$response->message, $response->code );
		return $response;
	}

	/**
	 *	Returns last connection error message, if connecting failed.
	 *	Otherwise NULL.
	 *	@access		public
	 *	@return		null|string
	 */
	public function getConnectError(){
		return $this->socket->getError();
	}

	static public function getInstance( $host, $port = 25 ){
		return new self( $host, $port );
	}

	/**
	 *	Sends mail using a socket connection to a remote SMTP server.
	 *	@access		public
	 *	@param		Message		$message		Mail message object
	 *	@throws		\RuntimeException		if message has no mail sender
	 *	@throws		\RuntimeException		if message has no mail receivers
	 *	@throws		\RuntimeException		if message has no mail body parts
	 *	@throws		\RuntimeException		if connection to SMTP server failed
	 *	@throws		\RuntimeException		if sending mail failed
	 *	@return		object  	Self instance for chaining.
	 */
	public function send( Message $message ){
		$delim		= Message::$delimiter;
		if( !$message->getSender() )
			throw new \RuntimeException( 'No mail sender set' );
		if( !$message->getRecipients( 'to') )
			throw new \RuntimeException( 'No mail receiver(s) set' );
		if( !$message->getParts() )
			throw new \RuntimeException( 'No mail body parts set' );
		$content	= MessageRenderer::create()->render( $message );

		$this->socket->open();
		try{
			$this->checkResponse( array( 220 ) );
			$this->sendChunk( "EHLO ".$this->host );
			$this->checkResponse( array( 250 ) );
			if( $this->isSecure ){
				$this->sendChunk( "STARTTLS" );
				$this->checkResponse( array( 220 ) );
				$this->socket->enableCrypto( TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT );
			}
			if( $this->username && $this->password ){
				$this->sendChunk( "AUTH LOGIN" );
				$this->checkResponse( array( 334 ) );
				$this->sendChunk( base64_encode( $this->username ) );
				$this->checkResponse( array( 334 ) );
				$this->sendChunk( base64_encode( $this->password ) );
				$this->checkResponse( array( 235 ) );
			}
			$sender		= $message->getSender();
			$this->sendChunk( "MAIL FROM: <".$sender->getAddress().">" );
			$this->checkResponse( array( 250 ) );
			foreach( $message->getRecipients( 'to' ) as $receiver ){
				$this->sendChunk( "RCPT TO: <".$receiver->getAddress().">" );
				$this->checkResponse( array( 250 ) );
			}
			$this->sendChunk( "DATA" );
			$this->checkResponse( array( 354 ) );
			$this->sendChunk( $content );
			$this->sendChunk( '.' );
			$this->checkResponse( array( 250 ) );
			$this->sendChunk( "QUIT" );
			$this->checkResponse( array( 221 ) );
			$this->socket->close();
		}
		catch( \Exception $e ){
			$this->socket->close();
			throw new \RuntimeException( $e->getMessage(), $e->getCode(), $e->getPrevious() );
		}
		return $this;
	}

	protected function sendChunk( $message ){
		if( $this->verbose )
			print PHP_EOL . ' > '.$message . PHP_EOL;
		return $this->socket->sendChunk( $message.Message::$delimiter );
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
	 *	@param		integer		$port			SMTP server port
	 * 	@param		boolean		$detectSecure	Flag: set secure depending on port (default: yes)
	 *	@return		object  	Self instance for chaining.
	 */
	public function setPort( $port, $detectSecure = TRUE ){
		$this->port		= $port;
		if( $detectSecure )
			$this->setSecure( in_array( $port, array( 465, 587 ) ) );
		return $this;
	}

	public function setSecure( $secure ){
		$this->isSecure = (bool) $secure;
		return $this;
	}

	public function setSocket( SmtpSocket $socket ){
		$this->socket	= $socket;
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
