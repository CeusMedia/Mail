<?php
/**
 *	Collector container for mails.
 *
 *	Copyright (c) 2007-2015 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;
/**
 *	Collector container for mails
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Message{

	/**	@var		array					$parts			List of attachments */
	protected $attachments					= array();
	/**	@var		string					$delimiter		Line separator, for some reasons only \n must be possible */
	public static $delimiter				= "\r\n";
	/**	@var		integer					$lineLength		Maximum line length of mail content */
	public static $lineLength				= 75;
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
	/**	@var		string					$mailer			Mailer agent */
	protected $userAgent					= 'CeusMedia/Mail/0.1';

	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct()
	{
		$this->headers		= new \CeusMedia\Mail\Header\Section();
	}

	public function addAttachment( \CeusMedia\Mail\Part\Attachment $attachment )
	{
		return $this->addPart( $attachment );
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Header\Field	$field		Mail header field object
	 *	@return		void
	 */
	public function addHeader( \CeusMedia\Mail\Header\Field $field )
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
		$field	= new \CeusMedia\Mail\Header\Field( $key, $value );
		return $this->addHeader( $field );
	}

	/**
	 *	Sets mail body part.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Part	$part		Part of mail
	 *	@return		void
	 */
	public function addPart( \CeusMedia\Mail\Part $part )
	{
		$this->parts[]	= $part;
		return $this;
	}

	public function addRecipient( $address, $name = NULL, $type = "To" )
	{
		if( !in_array( strtoupper( $type ), array( "TO", "CC", "BCC" ) ) )
			throw new InvalidArgumentException( 'Invalid recipient type' );
		$this->recipients[]	= (object) array(
			'address'	=> $address,
			'name'		=> $name,
			'type'		=> strtoupper( $type )
		);
		$recipient	= '<'.$address.'>';
		if( strlen( trim( $name ) ) ){
			$recipient	= self::encodeIfNeeded( $name ).' '.$recipient;
		}
		if( strtoupper( $type ) !== "BCC" )
			$this->addHeaderPair( ucFirst( strtolower( $type ) ), $recipient );
		return $this;
	}

	public function attachFile( $fileName, $mimeType, $encoding = NULL ){
		$attachment	= new \CeusMedia\Mail\Part\Attachment();
		$attachment->setFile( $fileName, $mimeType, $encoding );
		return $this->addAttachment( $attachment );
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		quoted-printable (default) or base64
	 *	@return		string
	 *	@throws		InvalidArgumentException	if given encoding is not supported
	 */
	static public function encodeIfNeeded( $string, $encoding = "quoted-printable" ){
		if( preg_match( "/^[\w\s\.-:#]+$/", $string ) )
			return $string;
		switch( strtolower( $encoding ) ){
			case 'quoted-printable':
				return "=?UTF-8?Q?".quoted_printable_encode( $string )."?=";
			case 'base64':
				return "=?UTF-8?B?".base64_encode( $string )."?=";
		}
		throw new InvalidArgumentException( 'Unsupported encoding: '.$encoding );
	}

	/**
	 *	Returns list set attachment parts.
	 *	@access		public
	 *	@return		array
	 */
	public function getAttachments()
	{
		$list	= array();
		foreach( $this->parts as $part )
			if( $part instanceof \CeusMedia\Mail\Part\Attachment )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Returns set headers.
	 *	@access		public
	 *	@return		array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	static public function getInstance(){
		return new self;
	}

	/**
	 *	Returns list of set body parts.
	 *	@access		public
	 *	@param		boolean		$withAttachments	Flag: return attachment parts also
	 *	@return		array
	 */
	public function getParts( $withAttachments = FALSE ){
		if( $withAttachments )
			return $this->parts;
		$list	= array();
		foreach( $this->parts as $part )
			if( !( $part instanceof \CeusMedia\Mail\Part\Attachment ) )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Returns set recipient addresses.
	 *	@access		public
	 *	@return		string
	 */
	public function getRecipients()
	{
		return $this->recipients;
	}

	/**
	 *	Returns assigned mail sender.
	 *	@access		public
	 *	@return		string
	 */
	public function getSender()
	{
		return $this->sender;
	}

	/**
	 *	Returns set mail subject.
	 *	@access		public
	 *	@param		string|NULL		$encoding		Types: quoted-printable, base64. Default: none
	 *	@return		string
	 */
	public function getSubject( $encoding = NULL )
	{
		if( $encoding )
			return self::encodeIfNeeded( $this->subject, $encoding );
		return $this->subject;
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
		$part	= new \CeusMedia\Mail\Part\HTML( $content );
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
		return $this;
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
			$sender	= self::encodeIfNeeded( $name ).' '.$sender;
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
		$part	= new \CeusMedia\Mail\Part\Text( $content );
		if( $charset )
			$part->setCharset( $charset );
		if( $encoding )
			$part->setEncoding( $encoding );
		$this->parts[]	= $part;
		return $this;
	}
}
?>
