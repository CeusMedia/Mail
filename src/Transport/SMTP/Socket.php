<?php
/**
 *	...
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
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport\SMTP;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://www.knownhost.com/wiki/email/troubleshooting/error-numbers
 *	@see			http://www.serversmtp.com/en/smtp-error
 */
class Socket{

	protected $host;

	protected $port;

	protected $errorNumber		= 0;

	protected $errorMessage		= '';

	/** @var	resource|NULL	$connection */
	protected $connection;

	protected $timeout			= 5;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$host		SMTP server host name
	 *	@param		integer		$port		SMTP server port
	 *	@param		integer		$timeout	Timeout (in seconds) on opening connection
	 *	@return		void
	 */
	public function __construct( $host = NULL, $port = NULL, $timeout = NULL ){
		if( !is_null( $host ) && strlen( trim( $host ) ) )
			$this->setHost( $host );
		if( is_integer( $port ) )
			$this->setPort( abs( $port ) );
		if( is_integer( $timeout ) )
			$this->setPort( abs( $timeout ) );
	}

	public function __destruct(){
		$this->close();
	}

	/**
	 *	Alias for getInstance.
	 *	@access		public
	 *	@static
	 *	@param		string		$host		SMTP server host name
	 *	@param		integer		$port		SMTP server port
	 *	@param		integer		$timeout	Timeout (in seconds) on opening connection
	 *	@return		self
	 *	@deprecated	use getInstance instead
	 *	@todo		to be removed
	 */
	public static function create( $host = NULL, $port = NULL, $timeout = NULL ){
		return static::getInstance( $host, $port, $timeout );
	}

	/**
	 *	Closes open connection.
	 *	@access		public
	 *	@return		self
	 */
	public function close(){
		if( $this->connection ){
			fclose( $this->connection );
			$this->connection	= NULL;
		}
		return $this;
	}

	/**
	 *	Sets cryptography mode.
	 *	@access		public
	 *	@param		boolean		$enable		Power switch
	 *	@param		integer		$crypto		Cryptography mode
	 *	@throws		\RuntimeException		if connection is not open
	 *	@return		self
	 */
	public function enableCrypto( $enable, $crypto ){
		if( !$this->connection )
			throw new \RuntimeException( 'Not connected' );
		stream_socket_enable_crypto( $this->connection, $enable, $crypto );
		return $this;
	}

	/**
	 *	Returns last error as set of number and message.
	 *	@access		public
	 *	@return		array
	 */
	public function getError(){
		return array(
			'errorNumber'	=> $this->errorNumber,
			'errorMessage'	=> $this->errorMessage,
		);
	}

	/**
	 *	Returns last error message.
	 *	@return		string
	 */
	public function getErrorMessage(){
		return $this->errorMessage;
	}

	/**
	 *	Returns last error number.
	 *	@return		integer
	 */
	public function getErrorNumber(){
		return $this->errorNumber;
	}

	/**
	 *	Returns set host.
	 *	@access		public
	 *	@return		null|string
	 */
	public function getHost(){
		return $this->host;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@param		string		$host		SMTP server host name
	 *	@param		integer		$port		SMTP server port
	 *	@param		integer		$timeout	Timeout (in seconds) on opening connection
	 *	@return		self
	 */
	public static function getInstance( $host = NULL, $port = NULL, $timeout = NULL ){
		return new self( $host, $port, $timeout );
	}

	/**
	 *	Returns set port.
	 *	@access		public
	 *	@return		null|integer
	 */
	public function getPort(){
		return $this->port;
	}

	/**
	 *	Returns set timeout.
	 *	@access		public
	 *	@return		integer
	 */
	public function getTimeout(){
		return $this->timeout;
	}

	/**
	 *	Open socket connection.
	 *	@access		public
	 *	@param		boolean		$forceReopen	Flag: close current and open new connection (default: no)
	 *	@throws		\RuntimeException			if not host is set
	 *	@throws		\RuntimeException			if not port is set
	 *	@throws		\RuntimeException			if connection failed
	 *	@return		self
	 */
	public function open( $forceReopen = FALSE ){
		if( $this->connection ){
			if( $forceReopen )
				$this->close();
			else
				return $this;
		}
		if( !$this->host )
			throw new \RuntimeException( 'No host set' );
		if( !$this->port )
			throw new \RuntimeException( 'No port set' );
		$this->connection	= fsockopen(
			$this->host,
			$this->port,
			$this->errorNumber,
			$this->errorMessage,
			$this->timeout
		);
		if( !$this->connection )
			throw new \RuntimeException( 'Connection to SMTP server "'.$this->host.':'.$this->port.'" failed' );
		return $this;
	}

	/**
	 *	Returns parsed response from SMTP server.
	 *	@access		public
	 *	@param		integer		$length			Size of chunks
	 *	@throws		\RuntimeException			if connection is not open
	 *	@throws		\RuntimeException			if request failed
	 *	@return		object
	 */
	public function readResponse( $length ){
		if( !$this->connection )
			throw new \RuntimeException( 'Not connected' );

		$lastLine	= FALSE;
		$code		= NULL;
		$buffer		= array();
		$raw		= array();
		do{
			$response	= fgets( $this->connection, $length );
			$raw[]		= rtrim( $response, "\r\n" );
			$matches	= array();
			preg_match( '/^([0-9]{3})( |-)(.+)$/', trim( $response ), $matches );
			if( !$matches )
				throw new \RuntimeException( 'SMTP response not understood: '.$lastLine );
			$code		= (int) $matches[1];
			$buffer[]	= $matches[3];
			$lastLine	= $matches[2] === " ";
		}
		while( $response && !$lastLine );
		return (object) array(
			'code'		=> $code,
			'message'	=> join( "\n", $buffer ),
			'raw'		=> $raw,
		);
	}

	/**
	 *	Set host.
	 *	@access		public
	 *	@param		string		$host		SMTP server host name
	 *	@return		self
	 */
	public function setHost( $host ){
		$this->host	= $host;
		return $this;
	}

	/**
	 *	Set port.
	 *	@access		public
	 *	@param		integer		$port		SMTP server port
	 *	@return		self
	 */
	public function setPort( $port ){
		$this->port	= $port;
		return $this;
	}

	/**
	 *	Set timeout (in seconds) on opening connection.
	 *	@access		public
	 *	@param		integer		$seconds	Timeout (in seconds) on opening connection
	 *	@return		self
	 */
	public function setTimeout( $seconds ){
		$this->timeout	= $seconds;
		return $this;
	}

	/**
	 *	Sends command or data to SMTP server.
	 *	@access		public
	 *	@param		string		$content	Message to send to SMTP server
	 *	@return		integer		Number of written bytes
	 *	@throws		\RuntimeException		if connection is not open
	 */
	public function sendChunk( $content ){
		if( !$this->connection )
			throw new \RuntimeException( 'Not connected' );
		return fwrite( $this->connection, $content );
	}
}
