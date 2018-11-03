<?php
/**
 *	Collector container for mails.
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use \CeusMedia\Mail\Address as Address;
use \CeusMedia\Mail\Address\Collection as AddressCollection;
use \CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

/**
 *	Collector container for mails
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Message{

	/**	@var		string					$delimiter		Line separator, for some reasons only \n must be possible */
	public static $delimiter				= "\r\n";
	/**	@var		integer					$lineLength		Maximum line length of mail content */
	public static $lineLength				= 75;
	/**	@var		array					$parts			List of mail parts */
	protected $parts						= array();
	/**	@var		MessageHeaderSection	$headers		Mail header section */
	protected $headers;
	/**	@var		string					$sender			Sender mail address */
	protected $sender;
	/**	@var		string					$recipients		List of recipients */
	protected $recipients	= array(
		'to'	=> array(),
		'cc'	=> array(),
		'bcc'	=> array(),
	);
	/**	@var		string					$subject		Mail subject */
	protected $subject;
	/**	@var		string					$mailer			Mailer agent */
	protected $userAgent					= 'CeusMedia::Mail/2.0';

	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct(){
		$this->headers				= new MessageHeaderSection();
		$this->recipients['to']		= new AddressCollection();
		$this->recipients['cc']		= new AddressCollection();
		$this->recipients['bcc']	= new AddressCollection();
	}

	/**
	 *	Add file as attachment part.
	 *	@access		public
	 *	@param		string		$filePath		Path of file to add
	 *	@param		string		$mimeType		Optional: MIME type of file
	 *	@param		string		$encoding		Optional: Encoding to apply
	 *	@param		string		$fileName		Optional: Name of file
	 *	@return		object		Message object for chaining
	 */
	public function addAttachment( $filePath, $mimeType = NULL, $encoding = NULL, $fileName = NULL ){
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFile( $filePath, $mimeType, $encoding );
		if( $fileName )
			$part->setFileName( $fileName );
		return $this->addPart( $part );
	}

	/**
	 *	Add file as attachment part.
	 *	Alias for addAttachment.
	 *	@access		public
	 *	@param		string		$filePath		Path of file to add
	 *	@param		string		$mimeType		Optional: MIME type of file
	 *	@param		string		$encoding		Optional: Encoding to apply
	 *	@param		string		$fileName		Optional: Name of file
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addAttachment instead
	 *	@todo		to be removed in 2.0
	 */
	public function addFile( $filePath, $mimeType = NULL, $encoding = NULL, $fileName = NULL ){
		return $this->addAttachment( $filePath, $mimeType, $encoding, $fileName );
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		MessageHeaderField	$field		Mail header field object
	 *	@return		object		Message object for chaining
	 */
	public function addHeader( MessageHeaderField $field ){
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
		$field	= new MessageHeaderField( $key, $value );
		return $this->addHeader( $field );
	}

	/**
	 *	Add HTML part by content.
	 *	@access		public
	 *	@param		string		$content		HTML content to add as message part
	 *	@param		string		$charset		Optional: Character set (default: UTF-8)
	 *	@param		string		$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 */
	public function addHtml( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		return $this->addPart( new \CeusMedia\Mail\Message\Part\HTML( $content, $charset, $encoding ) );
	}

	/**
	 *	Add image for HTML part by content.
	 *	Alias for addInlineImage.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$filePath		File Path of image to embed
	 *	@param		string		$mimeType		Optional: MIME type of file
	 *	@param		string		$encoding		Optional: Encoding to apply
	 *	@return		object		Message object for chaining
	 *	@deprecated	use addInlineImage instead
	 *	@todo		remove in 2.1
	 */
	public function addHtmlImage( $id, $filePath, $mimeType = NULL, $encoding = NULL ){
		return $this->addInlineImage( $id, $filePath, $mimeType, $encoding );
	}

	/**
	 *	Add image for HTML part by content.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$filePath		File Path of image to embed
	 *	@param		string		$mimeType		Optional: MIME type of file
	 *	@param		string		$encoding		Optional: Encoding to apply
	 *	@return		object		Message object for chaining
	 */
	public function addInlineImage( $id, $filePath, $mimeType = NULL, $encoding = NULL ){
		$part	= new \CeusMedia\Mail\Message\Part\InlineImage( $id, $filePath, $mimeType, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	Add forwared mail part by plain content.
	 *	@access		public
	 *	@param		string		$content		Nested mail content to add as message part
	 *	@param		string		$charset		Optional: Character set (default: UTF-8)
	 *	@param		string		$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 */
	public function addMail( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		$part	= new \CeusMedia\Mail\Message\Part\Mail( $content, $mimeType, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	General way to add another mail part.
	 *	More specific: addText, addHtml, addHtmlImage, addAttachment.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Message\Part	$part		Part of mail
	 *	@return		object							Message object for chaining
	 */
	public function addPart( \CeusMedia\Mail\Message\Part $part ){
		$this->parts[]	= $part;
		return $this;
	}

	public function addRecipient( $participant, $name = NULL, $type = "To" ){
		if( is_string( $participant ) )
			$participant	= new Address( $participant );
		if( !is_a( $participant, "\CeusMedia\Mail\Address" ) )
			throw new \InvalidArgumentException( 'Invalid value of first argument' );
		if( !in_array( strtoupper( $type ), array( "TO", "CC", "BCC" ) ) )
			throw new \InvalidArgumentException( 'Invalid recipient type' );

		if( $name )
			$participant->setName( $name );
		$this->recipients[strtolower( $type )]->add( $participant );
		$recipient	= '<'.$participant->getAddress().'>';
		if( strlen( trim( $name ) ) ){
			$recipient	= self::encodeIfNeeded( $name ).' '.$recipient;
		}
		$recipient	= $participant->get();
		if( strtoupper( $type ) !== "BCC" ){
			$fields	= $this->headers->getFieldsByName( $type );
			foreach( $fields as $field )
				if( $field->getValue() == $recipient )
					return $this;
			$this->addHeaderPair( ucFirst( strtolower( $type ) ), $recipient );
		}
		return $this;
	}

	public function addReplyTo( $participant, $name = NULL ){
		if( is_string( $participant ) )
			$participant	= new Address( $participant );
		if( !is_a( $participant, "\CeusMedia\Mail\Address" ) )
			throw new \InvalidArgumentException( 'Invalid value of first argument' );
		if( $name )
			$participant->setName( $name );
		$this->addHeaderPair( 'Reply-To', $participant->get() );
		return $this;
	}

	/**
	 *	Add plaintext part by content.
	 *	@access		public
	 *	@param		string		$content		Plaintext content to add as message part
	 *	@param		string		$charset		Optional: Character set (default: UTF-8)
	 *	@param		string		$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		object		Message object for chaining
	 */
	public function addText( $content, $charset = 'UTF-8', $encoding = 'base64' ){
		$part	= new \CeusMedia\Mail\Message\Part\Text( $content, $charset, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	Alias for getInstance.
	 *	@access		public
	 *	@static
	 *	@return		self
	 *	@deprecated	use getInstance instead
	 *	@todo		to be removed
	 */
	public static function create(){
		return static::getInstance();
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable (deprecated)
	 *	@return		string
	 *	@throws		InvalidArgumentException	if given encoding is not supported
	 */
	static public function encodeIfNeeded( $string, $encoding = "base64", $fold = TRUE ){
		if( preg_match( "/^[\w\s\.-:#]+$/", $string ) )
			return $string;
		switch( strtolower( $encoding ) ){
			case 'base64':
				return "=?UTF-8?B?".base64_encode( $string )."?=";
			case 'quoted-printable':
				if( !$fold )
					return "=?UTF-8?Q?".quoted_printable_encode( $string )."?=";
				$length	= Message::$lineLength;
				$delim	= Message::$delimiter;
				$lines	= str_split( $string, $length );
				foreach( $lines as $nr => $string ){
					$string	= quoted_printable_encode( $string );
					$string	= str_replace( '?', '=3F', $string );
					$string	= str_replace( ' ', '_', $string );
					$lines[$nr]	= "=?UTF-8?Q?".$string."?=";
				}
				return join( $delim."\t", $lines );
			default:
				throw new \InvalidArgumentException( 'Unsupported encoding: '.$encoding );
		}
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable (deprecated)
	 *	@return		string
	 *	@throws		InvalidArgumentException	if given encoding is not supported
	 */
	static public function decodeIfNeeded( $string, $encoding = "base64" ){
		if( !preg_match( "/^=\?(\S+)\?(\S)\?(.+)\?=$/", $string ) )
			return $string;
		$matches	= array();
		$list		= array();
		preg_match_all( "/(=\?.+\?=)/U", $string, $matches );
		foreach( $matches[1] as $string ){
			$charset	= preg_replace( "/^=\?(\S+)\?(\S)\?(.+)\?=$/s", '\\1', $string );
			$encoding	= preg_replace( "/^=\?(\S+)\?(\S)\?(.+)\?=$/s", '\\2', $string );
			$content	= preg_replace( "/^=\?(\S+)\?(\S)\?(.+)\?=$/s", '\\3', $string );
			switch( strtolower( $encoding ) ){
				case 'b':
					$list[]	= base64_decode( $content );
					break;
				case 'q':
					$content	= str_replace( "_", " ", $content );
					if( function_exists( 'imap_qprint' ) )
						$list[]	= imap_qprint( $content );
					else
						$list[]	= quoted_printable_decode( $content );
					break;
				default:
					throw new \InvalidArgumentException( 'Unsupported encoding: '.$encoding );
			}
		}
		return join( $list );
	}

	/**
	 *	Returns list set attachment parts.
	 *	@access		public
	 *	@param		boolean		$withInlineImages		Flag: list inline images, also
	 *	@return		array
	 */
	public function getAttachments( $withInlineImages = TRUE ){
		$list	= array();
		foreach( $this->parts as $part ){
			if( $part instanceof \CeusMedia\Mail\Message\Part\Attachment )
				$list[]	= $part;
			if( $part instanceof \CeusMedia\Mail\Message\Part\InlineImage )
				if( $withInlineImages )
					$list[]	= $part;
			if( $part instanceof \CeusMedia\Mail\Message\Part\Mail )
				$list[]	= $part;
		}
		return $list;
	}

	/**
	 *	Returns list set attachment parts.
	 *  Alias for getAttachments.
	 *	@access		public
	 *	@param		boolean		$withInlineImages		Flag: list inline images, also
	 *	@return		array
	 *	@deprecated	use getAttachments instead
	 *	@todo		remove in 2.1
	 */
	public function getFiles( $withInlineImages = TRUE ){
		return $this->getAttachments( $withInlineImages );
	}

	/**
	 *	Returns set headers.
	 *	@access		public
	 *	@return		array
	 */
	public function getHeaders(){
		return $this->headers;
	}

	/**
	 *	Returns list inline images to be embeded with HTML.
	 *	@access		public
	 *	@return		array
	 */
	public function getInlineImages(){
		$list	= array();
		foreach( $this->parts as $part )
			if( $part instanceof \CeusMedia\Mail\Message\Part\InlineImage )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	static public function getInstance(){
		return new static;
	}

	/**
	 *	Returns list attached mails.
	 *	@access		public
	 *	@return		array
	 */
	public function getMails(){
		$list	= array();
		foreach( $this->parts as $part )
			if( $part instanceof \CeusMedia\Mail\Message\Part\Mail )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Returns list of set body parts.
	 *	@access		public
	 *	@param		boolean		$withAttachments	Flag: return attachment parts also
	 *	@return		array
	 */
	public function getParts( $withAttachments = TRUE ){
		if( $withAttachments )
			return $this->parts;
		$list	= array();
		foreach( $this->parts as $part ){
			if( $part instanceof \CeusMedia\Mail\Message\Part\Attachment )
				if( !$withAttachments)
					continue;
			if( $part instanceof \CeusMedia\Mail\Message\Part\InlineImage )
				if( !$withAttachments)
					continue;
			$list[]	= $part;
		}
		return $list;
	}

	/**
	 *	Returns set recipient addresses.
	 *	@access		public
	 *	@return		array|AddressCollection
	 */
	public function getRecipients( $type = NULL ){
		if( $type ){
			if( !in_array( strtoupper( $type ), array( 'TO', 'CC', 'BCC' ) ) )
				throw new \DomainException( 'Type must be of to, cc or bcc' );
			return $this->recipients[strtolower( $type )];
		}
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
	 *	@param		string|NULL		$encoding		Optional: Types: base64, quoted-printable. Default: none
	 *	@return		string
	 */
	public function getSubject( $encoding = NULL ){
		if( $encoding )
			return self::encodeIfNeeded( $this->subject, $encoding, TRUE );
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

	public function setReadNotificationRecipient( $participant, $name = NULL ){
		if( is_string( $participant ) )
			$participant	= new Address( $participant );
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
	 *	@throws		\InvalidArgumentException		if given participant is neither string nor instance of \CeusMedia\Mail\Address
	 */
	public function setSender( $participant, $name = NULL ){
		if( is_string( $participant ) )
			$participant	= new Address( $participant );
		if( !is_a( $participant, "\CeusMedia\Mail\Address" ) )
			throw new \InvalidArgumentException( 'Invalid value of first argument' );
		if( $name )
			$participant->setName( $name );
		$this->sender	= $participant;
		$this->headers->removeFieldByName( 'From' );
		$this->addHeaderPair( "From", $participant->get() );
		return $this;
	}

	/**
	 *	Sets Mail Subject.
	 *	@access		public
	 *	@param		string		$subject	Subject of Mail
	 *	@return		object		Message object for chaining
	 */
	public function setSubject( $subject ){
		$this->subject	= \CeusMedia\Mail\Message::decodeIfNeeded( $subject );
		return $this;
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
