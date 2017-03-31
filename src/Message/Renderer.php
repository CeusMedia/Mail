<?php
/**
 *	Renderer for mails.
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
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;
/**
 *	Renderer for mails.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Renderer{

	static protected $userAgent		= "CeusMedia/Mail/2.0";

	/**
	 *	Get set mail agent.
	 *	@static
	 *	@access		public
	 *	@return		string		Mailer user agent
	 *	@deprecated	use Message::getUserAgent instead
	 *	@todo    	to be removed in 1.2
	 */
	static public function getAgent(){
		trigger_error( 'Use Message::getUserAgent instead', E_USER_DEPRECATED );
		return self::$userAgent;
	}

	static public function render( \CeusMedia\Mail\Message $message ){
		if( !count( $message->getParts() ) )
			throw new \RuntimeException( "No content part set" );
		if( !strlen( trim( $message->getSubject() ) ) )
			throw new \RuntimeException( "No subject set" );

		$delim			= \CeusMedia\Mail\Message::$delimiter;
		$mimeBoundary	= "------".md5( microtime( TRUE ) );
		$mimeBoundary1	= "------".md5( microtime( TRUE ) + 1 );

		$server		= empty( $_SERVER['SERVER_NAME'] ) ? 'localhost' : $_SERVER['SERVER_NAME'];

		$headers	= $message->getHeaders();
		$headers->setFieldPair( 'Message-ID', "<".sha1( microtime() )."@".$server.">" );
		$headers->setFieldPair( 'Date', date( "D, d M Y H:i:s O", time() ) );
		$headers->setFieldPair( 'Subject', $message->getSubject( 'base64' ) );
		$headers->setFieldPair( 'Content-Type', 'multipart/related;'.$delim.' boundary="'.$mimeBoundary.'"' );
		$headers->setFieldPair( 'MIME-Version', '1.0' );
		$headers->addFieldPair( 'X-Mailer', $message->getUserAgent() );

		$contents	= array( "This is a multi-part message in MIME format." );
		$contents[]	= "--".$mimeBoundary;
		$contents[]	= 'Content-Type: multipart/alternative; boundary="'.$mimeBoundary1.'"';
		$contents[]	= "";
		foreach( $message->getParts() as $part )
			$contents[]	= "--".$mimeBoundary1.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary1."--".$delim;
		foreach( $message->getAttachments() as $part )
			$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary."--".$delim;
		return $headers->toString().$delim.$delim.join( $delim, $contents );
	}

	/**
	 *	Sets mail agent for mailer header.
	 *	@static
	 *	@access		public
	 *	@param		string		$userAgent		Mailer user agent
	 *	@return		void
	 *	@deprecated	use Message::setUserAgent instead
	 *	@todo    	to be removed in 1.2
	 */
	static public function setAgent( $userAgent ){
		trigger_error( 'Use Message::setUserAgent instead', E_USER_DEPRECATED );
		self::$userAgent = $userAgent;
	}
}
?>
