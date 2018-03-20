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
	protected $address;
	protected $username;
	protected $password;
	protected $secure					= TRUE;
	protected $validateCertificates		= TRUE;

	public function __construct( $address, $username = NULL, $password = NULL, $secure = TRUE, $validateCertificates = TRUE ){
		if( is_string( $address ) )
			$address	= new Address( $address );
		$this->address	= $address;
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
					throw new \RuntimeException( 'No connected' );
				return FALSE;
			}
			return $this->connect();
		}
		if( $this->connection && is_resource( $this->connection ) )
			return TRUE;
		if( $strict )
			throw new \RuntimeException( 'No connected' );
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
		$flags	= $flags ? '/'.join( '/', $flags ) : '';

		$mx			= new Util\MX();
		$servers	= $mx->fromAddress( $this->address );
		if( !$servers )
			throw new \RuntimeException( 'No mail servers detected for address: '.$this->address->get() );

		foreach( $servers as $server ){
			$uri		= '{'.$server.':'.$port.$flags.'}INBOX';
			$resource	= imap_open( $uri, $this->username, $this->password, $options );
			if( $resource ){
				$this->connection	= $resource;
				return TRUE;
			}
		}
		if( $strict )
			throw new \RuntimeException( 'Connection to server failed' );
		return FALSE;
	}

	public function disconnect() {
		if( $this->checkConnection( FALSE, FALSE ) )
			return imap_close( $this->connection, CL_EXPUNGE );
		return TRUE;
	}

	public function getMailAsMessage( $mailId, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( !$header )
			throw new \RuntimeException( 'Invalid mail ID' );
		$body	= imap_body( $this->connection, $mailId, FT_UID | FT_PEEK );
		return (object) Message\Parser::parse( $header.PHP_EOL.PHP_EOL.$body );
	}

	public function getMailHeaders( $mailId, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		$header	= imap_fetchheader( $this->connection, $mailId, FT_UID );
		if( !$header )
			throw new \RuntimeException( 'Invalid mail ID' );
		return Message\Header\Parser::parse( $header );
	}

	public function index( $criteria = array(), $sort = SORTARRIVAL, $reverse = FALSE, $strict = TRUE ){
		$this->checkConnection( TRUE, $strict );
		return imap_sort( $this->connection, $sort, $reverse, SE_UID, join( ' ', $criteria ), 'UTF-8' );
	}

	public function setAuth( $username, $password ){
		$this->username	= $username;
		$this->password	= $password;
	}

	public function setMailFlag( $mailId, $flag ){
		$flags	= array( 'seen', 'answered', 'flagged', 'deleted', 'draft' );
		if( !in_array( strtolower( $flag ), $flags ) )
			throw new \RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag	= '\\'.ucfirst( strtolower( $flag ) );
		return imap_setflag_full( $this->connection, $mailId, $flag, ST_UID );
	}

	public function setSecure( $secure = TRUE, $validateCertificates = TRUE ){
		$this->secure				= $secure;
		$this->validateCertificates	= $validateCertificates;
	}

	public function unsetMailFlag( $mailId, $flag ){
		$flags	= array( 'seen', 'answered', 'flagged', 'deleted', 'draft' );
		if( !in_array( strtolower( $flag ), $flags ) )
			throw new \RangeException( 'Invalid flag, must be one of: '.join( ', ', $flags ) );
		$flag	= '\\'.ucfirst( strtolower( $flag ) );
		return imap_clearflag_full( $this->connection, $mailId, $flag, ST_UID );
	}

	public function removeMail( $mailId, $expunge = FALSE ){
		$this->checkConnection( TRUE );
		$result	= imap_delete( $this->connection, $mailId, FT_UID );
		if( $expunge )
			imap_expunge( $this->connection );
	}
}
