<?php
/**
 *	Collector container for mails.
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
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
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Message{

	/**	@var		string							$delimiter		Line separator, for some reasons only \n must be possible */
	public static $delimiter						= "\r\n";
	/**	@var		integer							$lineLength		Maximum line length of mail content */
	public static $lineLength						= 75;
	/**	@var		array							$parts			List of mail parts */
	protected $parts								= array();
	/**	@var		\CeusMedia\Mail\Header\Section	$headers		Mail header section */
	protected $headers;
	/**	@var		string							$sender			Sender mail address */
	protected $sender;
	/**	@var		string							$recipients		List of recipients */
	protected $recipients;
	/**	@var		string							$subject		Mail subject */
	protected $subject;
	/**	@var		string							$mailer			Mailer agent */
	protected $userAgent							= 'CeusMedia::Mail/1.1.0';

	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct(){
		$this->headers		= new \CeusMedia\Mail\Header\Section();
	}

	/**
	 *	Add attachment.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Part\Attachment	$attachment	Attachment object to add
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addFile instead
	 *	@todo    	to be removed in 1.2
	 */
	public function addAttachment( \CeusMedia\Mail\Part\Attachment $attachment ){
		trigger_error( 'Use addFile instead', E_USER_DEPRECATED );
		return $this->addPart( $attachment );
	}

	/**
	 *	Add file as attachment part.
	 *	@access		public
	 *	@param		string		$fileName		Path of file to add
	 *	@param		string		$mimeType		MIME type of file
	 *	@param		string		$encoding		Encoding to apply
	 *	@return		object		Message object for chaining
	 */
	public function addFile( $fileName, $mimeType = NULL, $encoding = NULL ){
		$part	= new \CeusMedia\Mail\Part\Attachment();
		$part->setFile( $fileName, $mimeType, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	Adds a header.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Header\Field	$field		Mail header field object
	 *	@return		object		Message object for chaining
	 */
	public function addHeader( \CeusMedia\Mail\Header\Field $field ){
		$this->headers->addField( $field );
		return $this;
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		string		$key		Key of header
	 *	@param		string		$value		Value of header
	 *	@return		object		Message object for chaining
	 */
	public function addHeaderPair( $key, $value ){
		$field	= new \CeusMedia\Mail\Header\Field( $key, $value );
		return $this->addHeader( $field );
	}

	/**
	 *	Add HTML part by content.
	 *	@access		public
	 *	@param		string		$content		HTML content to add as message part
	 *	@param		string		$charset		Character set (default: UTF-8)
	 *	@param		string		$encoding		Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 */
	public function addHtml( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		return $this->addPart( new \CeusMedia\Mail\Part\HTML( $content, $charset, $encoding ) );
	}

	/**
	 *	Add image for HTML part by content.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$fileName		File Path of image to embed
	 *	@param		string		$mimeType		MIME type of file
	 *	@param		string		$encoding		Encoding to apply
	 *	@return		object		Message object for chaining
	 */
	public function addHtmlImage( $id, $fileName, $mimeType = NULL, $encoding = NULL ){
		$part	= new \CeusMedia\Mail\Part\InlineImage( $id, $fileName, $mimeType, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	General way to add another mail part.
	 *	More specific: addText, addHtml, addHtmlImage, addFile.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Part	$part		Part of mail
	 *	@return		object			Message object for chaining
	 */
	public function addPart( \CeusMedia\Mail\Part $part ){
		$this->parts[]	= $part;
		return $this;
	}

	public function addRecipient( $participant, $name = NULL, $type = "To" ){
		if( is_string( $participant ) )
			$participant	= new \CeusMedia\Mail\Participant( $participant );
		if( !is_a( $participant, "\CeusMedia\Mail\Participant" ) )
			throw new \InvalidArgumentException( 'Invalid value of first argument' );
		if( !in_array( strtoupper( $type ), array( "TO", "CC", "BCC" ) ) )
			throw new \InvalidArgumentException( 'Invalid recipient type' );

		if( $name )
			$participant->setName( $name );
		$this->recipients[]	= $participant;
		$recipient	= '<'.$participant->getAddress().'>';
		if( strlen( trim( $name ) ) ){
			$recipient	= self::encodeIfNeeded( $name ).' '.$recipient;
		}
		$recipient	= $participant->get();
		if( strtoupper( $type ) !== "BCC" )
			$this->addHeaderPair( ucFirst( strtolower( $type ) ), $recipient );
		return $this;
	}

	/**
	 *	Add plaintext part by content.
	 *	@access		public
	 *	@param		string		$content		Plaintext content to add as message part
	 *	@param		string		$charset		Character set (default: UTF-8)
	 *	@param		string		$encoding		Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 */
	public function addText( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		return $this->addPart( new \CeusMedia\Mail\Part\Text( $content, $charset, $encoding ) );
	}

	/**
	 *	Attach a file.
	 *	Alias for addFile.
	 *	@access		public
	 *	@param		string		$fileName		Path of file to add
	 *	@param		string		$mimeType		MIME type of file
	 *	@param		string		$encoding		Encoding to apply
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addFile instead
	 *	@todo    	to be removed in 1.2
	 */
	public function attachFile( $fileName, $mimeType = NULL, $encoding = NULL ){
		trigger_error( 'Use addFile instead', E_USER_DEPRECATED );
		return $this->addFile( $fileName, $mimeType, $encoding );
	}

	/**
	 *	Embed image for HTML part.
	 *	Alias for addHtmlImage.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$fileName		File Path of image to embed
	 *	@param		string		$mimeType		MIME type of file
	 *	@param		string		$encoding		Encoding to apply
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addHtmlImage instead
	 *	@todo    	to be removed in 1.2
	 */
	public function embedImage( $id, $fileName, $mimeType = NULL, $encoding = NULL ){
		trigger_error( 'Use addHtmlImage instead', E_USER_DEPRECATED );
		return $this->addHtmlImage( $id, $fileName, $mimeType, $encoding );
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		base64 (default) or quoted-printable (deprecated)
	 *	@return		string
	 *	@throws		InvalidArgumentException	if given encoding is not supported
	 */
	static public function encodeIfNeeded( $string, $encoding = "base64" ){
		if( preg_match( "/^[\w\s\.-:#]+$/", $string ) )
			return $string;
		switch( strtolower( $encoding ) ){
			case 'base64':
				return "=?UTF-8?B?".base64_encode( $string )."?=";
			case 'quoted-printable':
				$string	= quoted_printable_encode( $string );
				$string	= str_replace( '?', '=3F', $string );
				$string	= str_replace( ' ', '_', $string );
				return "=?UTF-8?Q?".$string."?=";
		}
		throw new \InvalidArgumentException( 'Unsupported encoding: '.$encoding );
	}

	/**
	 *	Returns mail agent.
	 *	@access		public
	 *	@return		string
	 *	@deprecated	use getUserAgent instead
	 *	@todo   	to be removed in 1.2
	 */
	public function getAgent(){
		trigger_error( 'Use getUserAgent instead', E_USER_DEPRECATED );
		return $this->userAgent;
	}

	/**
	 *	Returns list set attachment parts.
	 *	@access		public
	 *	@return		array
	 */
	public function getAttachments(){
		$list	= array();
		foreach( $this->parts as $part ){
			if( $part instanceof \CeusMedia\Mail\Part\Attachment )
				$list[]	= $part;
			if( $part instanceof \CeusMedia\Mail\Part\InlineImage )
				$list[]	= $part;
		}
		return $list;
	}

	/**
	 *	Returns set headers.
	 *	@access		public
	 *	@return		array
	 */
	public function getHeaders(){
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
		foreach( $this->parts as $part ){
			if( $part instanceof \CeusMedia\Mail\Part\Attachment )
				if( !$withAttachments)
					continue;
			if( $part instanceof \CeusMedia\Mail\Part\InlineImage )
				if( !$withAttachments)
					continue;
			$list[]	= $part;
		}
		return $list;
	}

	/**
	 *	Returns set recipient addresses.
	 *	@access		public
	 *	@return		string
	 */
	public function getRecipients(){
		return $this->recipients;
	}

	/**
	 *	Returns assigned mail sender.
	 *	@access		public
	 *	@return		string
	 */
	public function getSender(){
		return $this->sender;
	}

	/**
	 *	Returns set mail subject.
	 *	@access		public
	 *	@param		string|NULL		$encoding		Types: base64, quoted-printable. Default: none
	 *	@return		string
	 */
	public function getSubject( $encoding = NULL ){
		if( $encoding )
			return self::encodeIfNeeded( $this->subject, $encoding );
		return $this->subject;
	}

	/**
	 *	Returns mail user agent.
	 *	@access		public
	 *	@return		string
	 */
	public function getUserAgent(){
		return $this->userAgent;
	}

	/**
	 *	Sets mail agent for mailer header.
	 *	@access		public
	 *	@param		string		$userAgent		Mailer user agent
	 *	@return		object		Message object for chaining
	 *	@deprecated	use setUserAgent instead
	 *	@todo   	to be removed in 1.2
	 */
	public function setAgent( $userAgent ){
		trigger_error( 'Use setUserAgent instead', E_USER_DEPRECATED );
		return $this->setUserAgent( $userAgent );
	}

	/**
	 *	...
	 *	Alias for addHtml.
	 *	@param		string		$content		HTML content to add as message part
	 *	@param		string		$charset		Character set (default: UTF-8)
	 *	@param		string		$encoding		Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addHtml or addPart instead
	 *	@todo    	to be removed in 1.2
	 */
	public function setHTML( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		trigger_error( 'Use addHtml instead', E_USER_DEPRECATED );
		return $this->addHtml( $content, $charset, $encoding );
	}

	public function setReadNotificationRecipient( $participant, $name = NULL ){
		if( is_string( $participant ) )
			$participant	= new \CeusMedia\Mail\Participant( $participant );
		if( $name )
			$participant->setName( $name );
		$this->headers->addFieldPair( 'Disposition-Notification-To', $participant->get() );
		return $this;
	}

	/**
	 *	Sets sender address and name.
	 *	@access		public
	 *	@param		string|object	$participant	Mail sender address or participant object
	 *	@param		string			$name			Mail sender name
	 *	@return		object			Message object for chaining
	 *	@throws		\InvalidArgumentException		if given participant is neither string nor instance of \CeusMedia\Mail\Participant
	 */
	public function setSender( $participant, $name = NULL ){
		if( is_string( $participant ) )
			$participant	= new \CeusMedia\Mail\Participant( $participant );
		if( !is_a( $participant, "\CeusMedia\Mail\Participant" ) )
			throw new \InvalidArgumentException( 'Invalid value of first argument' );
		if( $name )
			$participant->setName( $name );
		$this->sender	= $participant;
		$this->headers->setFieldPair( "From", $participant->get() );
		return $this;
	}

	/**
	 *	Sets Mail Subject.
	 *	@access		public
	 *	@param		string		$subject	Subject of Mail
	 *	@return		object		Message object for chaining
	 */
	public function setSubject( $subject ){
		$this->subject	= $subject;
		return $this;
	}

	/**
	 *	...
	 *	@param		string		$content		HTML content to add as message part
	 *	@param		string		$charset		Character set (default: UTF-8)
	 *	@param		string		$encoding		Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addText or addPart instead
	 *	@todo    	to be removed in 1.2
	 */
	public function setText( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		trigger_error( 'Use addText instead', E_USER_DEPRECATED );
		return $this->addText( $content, $charset, $encoding );
	}

	/**
	 *	Sets mail user agent for mailer header.
	 *	@access		public
	 *	@param		string		$userAgent		Mailer user agent
	 *	@return		object		Message object for chaining
	 */
	public function setUserAgent( $userAgent ){
		$this->userAgent = $userAgent;
		return $this;
	}
}
?>
