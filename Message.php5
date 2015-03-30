<?php
/**
 *	Collector container for mails.
 *
 *	Copyright (c) 2007-2013 Christian Würker (ceusmedia.de)
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
 *	@package		Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2013 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id: Mail.php5 1111 2013-09-30 06:28:11Z christian.wuerker $
 */
/**
 *	Collector container for mails
 *
 *	@category		cmModules
 *	@package		Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2013 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@version		$Id: Mail.php5 1111 2013-09-30 06:28:11Z christian.wuerker $
 */
class CMM_Mail_Message{

	/**	@var		array					$parts			List of attachments */
	protected $attachments					= array();
	/**	@var		string					$delimiter		Line separator, for some reasons only \n must be possible */
	public static $delimiter				= "\r\n";
	/**	@var		array					$parts			List of mail parts */
	protected $parts						= array();
	/**	@var		CMM_Mail_Header_Section	$headers		Mail header section */
	protected $headers;
	/**	@var		string					$sender			Sender mail address */
	protected $sender;
	/**	@var		string					$recipients		List of recipients */
	protected $recipients;
	/**	@var		string					$subject		Mail subject */
	protected $subject;
	/**	@var		string					$mailer		Mailer agent */
	protected $userAgent					= 'cmModules::CMM_Mail/0.1';

	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct()
	{
		$this->headers		= new CMM_Mail_Header_Section();
	}

	public function addAttachment( CMM_Mail_Part_Attachment $attachment )
	{
		$this->attachments[]	= $attachment;
		return $this;
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		CMM_Mail_Header_Field	$field		Mail header field object
	 *	@return		void
	 */
	public function addHeader( CMM_Mail_Header_Field $field )
	{
		$this->headers->addField( $field );
		return $this;
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		string		$key		Key of header
	 *	@param		string		$value		Value of header
	 *	@return		void
	 */
	public function addHeaderPair( $key, $value )
	{
		$this->headers->addField( new CMM_Mail_Header_Field( $key, $value ) );
		return $this;
	}

	/**
	 *	Sets mail body part.
	 *	@access		public
	 *	@param		CMM_Mail_Part_Abstract		$body		Body part of mail
	 *	@return		void
	 */
	public function addPart( CMM_Mail_Part_Abstract $body )
	{
		$this->parts[]	= $body;
		return $this;
	}

	public function addRecipient( $address, $name, $type = "To" )
	{
		if( !in_array( strtoupper( $type ), array( "TO", "CC", "BCC" ) ) )
			throw new InvalidArgumentException( 'Invalid recipient type' );
		$this->recipients[]	= (object) array(
			'address'	=> $address,
			'name'		=> $name,
			'type'		=> strtoupper( $type )
		);
		$recipient	= '<'.$address.'>';
		if( strlen( trim( $name ) ) )
			$recipient	= $name.' '.$recipient;
		if( strtoupper( $type ) !== "BCC" )
			$this->addHeaderPair( ucFirst( strtolower( $type ) ), $recipient );
		return $this;
	}

	public function attach( $fileName, $mimeType, $encoding = NULL ){
		$attachment	= new CMM_Mail_Part_Attachment();
		$attachment->setFile( $fileName, $mimeType, $encoding );
		$this->attachments[]	= $attachment;
		return $this;
	}

	/**
	 *	Returns set Headers.
	 *	@access		public
	 *	@return		array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 *	Returns Mail Body.
	 *	@access		public
	 *	@return		string
	 */
	public function getParts(){
		return $this->parts;
	}

	/**
	 *	Returns Recipient Addresses.
	 *	@access		public
	 *	@return		string
	 */
	public function getRecipients()
	{
		return $this->recipients;
	}

	public function getSender()
	{
		return $this->sender;
	}

	/**
	 *	Returns Mail Subject.
	 *	@access		public
	 *	@return		string
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	public function render()
	{
		if( !count( $this->parts ) )
			return "";
		$mimeBoundary	= "------".md5( microtime( TRUE ) );
		$mimeBoundary1	= $mimeBoundary."-1";

		$headers		= new CMM_Mail_Header_Section();
		foreach( $this->headers->getFields() as $field )
			$headers->addField( $field );
		
		$server		= empty( $_SERVER['SERVER_NAME'] ) ? 'localhost' : $_SERVER['SERVER_NAME'];

		$headers->setFieldPair( "Message-ID", "<".sha1( microtime() )."@".$server.">" );
		$headers->setFieldPair( "Date", date( "D, d M Y H:i:s O", time() ) );
		$headers->setFieldPair( "Subject", "=?utf-8?B?".base64_encode( $this->subject )."?=" );
		$headers->setFieldPair( "Content-Type", "multipart/mixed;\r\n boundary=\"".$mimeBoundary."\"" );
		$headers->setFieldPair( "MIME-Version", "1.0" );		
		$headers->addFieldPair( 'X-Mailer', $this->userAgent );

		$contents	= array( "This is a multi-part message in MIME format." );
		$contents[]	= "--".$mimeBoundary;
		$contents[]	= "Content-Type: multipart/alternative;\r\n boundary=\"".$mimeBoundary1."\"";
		$contents[]	= "";
		foreach( $this->parts as $part )
			$contents[]	= "--".$mimeBoundary1."\r\n".$part->render();
		$contents[]	= "--".$mimeBoundary1."--";
		$contents[]	= "";
		$contents[]	= "";
		foreach( $this->attachments as $part )
			$contents[]	= "--".$mimeBoundary."\r\n".$part->render();
		$contents[]	= "--".$mimeBoundary."--\r\n";
		return $headers->toString()."\r\n\r\n".join( "\r\n", $contents );
	}

	/**
	 *	Sets mail agent for mailer header.
	 *	@access		public
	 *	@param		string		$userAgent		Mailer user agent
	 *	@return		void
	 */
	public function setAgent( $userAgent ){
		$this->userAgent = $userAgent;
		return $this;
	}

	public function setHTML( $content, $charset = NULL, $encoding = NULL ){
		$part	= new CMM_Mail_Part_HTML( $content );
		if( $charset )
			$part->setCharset( $charset );
		if( $encoding )
			$part->setEncoding( $encoding );
		$this->parts[]	= $part;
		return $this;
	}

	public function setReadNotificationRecipient( $address, $name = NULL ){
		$recipient	= $address;
		if( strlen( trim( $name ) ) )
			$recipient	= $name.' <'.$recipient.'>';
		$this->headers->addFieldPair( 'Disposition-Notification-To', $recipient );
	}

	/**
	 *	Sets sender address and name.
	 *	@access		public
	 *	@param		string		$address	Mail sender address
	 *	@param		string		$name		Mail sender address
	 *	@return		void
	 *	@throws		Exception
	 */
	public function setSender( $address, $name = NULL )
	{
		$this->sender	= (object) array(
			'address'	=> $address,
			'name'		=> $name
		);
		$sender	= '<'.$address.'>';
		if( strlen( trim( $name ) ) )
			$sender	= $name.' '.$sender;
		$this->addHeaderPair( "From", $sender );
		return $this;
	}

	/**
	 *	Sets Mail Subject.
	 *	@access		public
	 *	@param		string		$subject	Subject of Mail
	 *	@return		void
	 */
	public function setSubject( $subject )
	{
		$this->subject	= $subject;
		return $this;
	}

	public function setText( $content, $charset = NULL, $encoding = NULL ){
		$part	= new CMM_Mail_Part_Text( $content );
		if( $charset )
			$part->setCharset( $charset );
		if( $encoding )
			$part->setEncoding( $encoding );
		$this->parts[]	= $part;
		return $this;
	}
}
?>
