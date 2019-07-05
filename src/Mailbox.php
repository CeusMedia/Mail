<?php
/**
 *	Handler for IMAP mailboxes.
 *
 *	Copyright (c) 2017-2018 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use \CeusMedia\Mail\Message\Parser as MessageParser;
use \CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;

/**
 *	Handler for IMAP mailboxes.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Mailbox{

	protected $connection;
	protected $username;
	protected $password;
	protected $host;
	protected $secure					= TRUE;
	protected $validateCertificates		= TRUE;
	protected $error;

	public function __construct( $host, $username = NULL, $password = NULL, $secure = TRUE, $validateCertificates = TRUE ){
		$this->checkExtensionInstalled( TRUE );
		$this->setHost( $host );
		if( $username && $password )
			$this->setAuth( $username, $password );
		$this->setSecure( $secure, $validateCertificates );
	}

	public function __destruct() {
		$this->disconnect();
	}

	protected function checkConnection( $connect = FALSE, $strict = TRUE ){
		if( !$this->connection || !imap_ping( $this->connection ) ){
			if( !$connect ){
				if( $strict )
					throw new \RuntimeException( 'Not connected' );
				return FALSE;
			}
			return $this->connect();
		}
		if( $this->connection && is_resource( $this->connection ) )
			return TRUE;
		if( $strict )
			throw new \RuntimeException( 'Not connected' );
		return FALSE;
	}

	static public function checkExtensionInstalled( $strict = TRUE ){
		if( extension_loaded( 'imap' ) )
			return TRUE;
		if( $strict )
			throw new \RuntimeException( 'PHP extension "imap" not installed, but needed' );
		return FALSE;
	}

	public function connect( $strict = TRUE ){
		$port		= 143;
		$flags		= array();
		$options	= 0;
		if( $this->secure ){
			$port		= 993;
			$flags[]	= 'ssl';
			if( $this->validateCertificates ){
				$flags[]	= 'validate-cert';
				$options	|= OP_SECURE;
			}
		}
		if( !$this->validateCertificates )
			$flags[]	= 'novalidate-cert';
		$flags	= $flags ? '/'.join( '/', $flags ) : '';

		$uri		= '{'.$this->host.':'.$port.$flags.'}INBOX';
		$resource	= imap_open( $uri, $this->username, $this->password, $options );
		if( $resource ){
			$this->connection	= $resource;
			return TRUE;
		}
		$this->error	= imap_last_error();
		if( $strict )
			throw new \RuntimeException( 'Connection to server failed' );
		return FALSE;
	}

	public function disconnect() {
		if( $this->checkConnection( FALSE, FALSE ) )
			return imap_close( $this->connection, CL_EXPUNGE );
		return TRUE;
	}

	public static function getInstance( $host, $username = NULL, $password = NULL, $secure = TRUE, $validateCertificates = TRUE ){
		return new self( $host, $username, $password, $secure, $validateCertificates );
	}

	public function getError(){
		return $this->error;
	}

	public function getMail( $mailId, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( !$header )
			throw new \RuntimeException( 'Invalid mail ID' );
		$body	= imap_body( $this->connection, $mailId, FT_UID | FT_PEEK );
		return $header.PHP_EOL.PHP_EOL.$body;
	}

	public function getMailAsMessage( $mailId, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( !$header )
			throw new \RuntimeException( 'Invalid mail ID' );
		$body	= imap_body( $this->connection, $mailId, FT_UID | FT_PEEK );
		return (object) MessageParser::parse( $header.PHP_EOL.PHP_EOL.$body );
	}

	public function getMailHeaders( $mailId, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( !$header )
			throw new \RuntimeException( 'Invalid mail ID' );
		return MessageHeaderParser::parse( $header );
	}

	public function index( $criteria = array(), $sort = SORTARRIVAL, $reverse = FALSE, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		if( !is_array( $criteria ) && is_string( $criteria ) )
			$criteria	= array( $criteria );
		return imap_sort( $this->connection, $sort, $reverse, SE_UID, join( ' ', $criteria ), 'UTF-8' );
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		object		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setAuth( $username, $password ){
		$this->username	= $username;
		$this->password	= $password;
		return $this;
	}

	public function setHost( $host ){
		$this->host	= $host;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		object		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setMailFlag( $mailId, $flag ){
		$flags	= array( 'seen', 'answered', 'flagged', 'deleted', 'draft' );
		if( !in_array( strtolower( $flag ), $flags ) )
			throw new \RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag	= '\\'.ucfirst( strtolower( $flag ) );
		imap_setflag_full( $this->connection, $mailId, $flag, ST_UID );
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		object		Own instance for chainability
	 *	@todo		code doc
	 */
	public function setSecure( $secure = TRUE, $validateCertificates = TRUE ){
		$this->secure				= $secure;
		$this->validateCertificates	= $validateCertificates;
		return $this;
	}

	public function setTimeout( $type, $seconds ){
		$timeoutTypes	= array(
			IMAP_OPENTIMEOUT,
			IMAP_READTIMEOUT,
			IMAP_WRITETIMEOUT,
			IMAP_CLOSETIMEOUT
		);
		if( !in_array( $type, $timeoutTypes ) )
			throw new \InvalidArgumentException( 'Invalid timeout type' );
		imap_timeout( $timeoutTypes[$type], $seconds );
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		object		Own instance for chainability
	 *	@todo		code doc
	 */
	public function unsetMailFlag( $mailId, $flag ){
		$flags	= array( 'seen', 'answered', 'flagged', 'deleted', 'draft' );
		if( !in_array( strtolower( $flag ), $flags ) )
			throw new \RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag	= '\\'.ucfirst( strtolower( $flag ) );
		imap_clearflag_full( $this->connection, $mailId, $flag, ST_UID );
		return $this;
	}

	public function removeMail( $mailId, $expunge = FALSE ){
		$this->checkConnection( TRUE );
		$result	= imap_delete( $this->connection, $mailId, FT_UID );
		if( $expunge )
			imap_expunge( $this->connection );
	}
}
