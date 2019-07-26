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
use \CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use \CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

use \CeusMedia\Mail\Message\Part as MessagePart;
use \CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use \CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use \CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use \CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use \CeusMedia\Mail\Message\Part\Text as MessagePartText;

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
class Message
{
	/**	@var		string					$delimiter		Line separator, for some reasons only \n must be possible */
	public static $delimiter				= "\r\n";

	/**	@var		integer					$lineLength		Maximum line length of mail content */
	public static $lineLength				= 75;

	/**	@var		array					$parts			List of mail parts */
	protected $parts						= array();

	/**	@var		MessageHeaderSection	$headers		Mail header section */
	protected $headers;

	/**	@var		Address					$sender			Sender mail address */
	protected $sender;

	/**	@var		array					$recipients		List of recipients */
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
	public function __construct()
	{
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
	 *	@return		self		Message object for chaining
	 */
	public function addAttachment( string $filePath, ?string $mimeType = NULL, ?string $encoding = NULL, ?string $fileName = NULL ): self
	{
		$part	= new MessagePartAttachment();
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
	 *	@return		self		Message object for chaining
	 *	@deprecated	use addAttachment instead
	 *	@todo		to be removed in 2.0
	 */
	public function addFile( string $filePath, ?string $mimeType = NULL, ?string $encoding = NULL, ?string $fileName = NULL ): self
	{
		return $this->addAttachment( $filePath, $mimeType, $encoding, $fileName );
	}

	/**
	 *	Adds a header.
	 *	@access		public
	 *	@param		MessageHeaderField	$field		Mail header field object
	 *	@return		self		Message object for chaining
	 */
	public function addHeader( MessageHeaderField $field ): self
	{
		$this->headers->addField( $field );
		return $this;
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		string		$key		Key of header
	 *	@param		string		$value		Value of header
	 *	@return		self		Message object for chaining
	 */
	public function addHeaderPair( string $key, string $value ): self
	{
		$field	= new MessageHeaderField( $key, $value );
		return $this->addHeader( $field );
	}

	/**
	 *	Add HTML part by content.
	 *	@access		public
	 *	@param		string		$content		HTML content to add as message part
	 *	@param		string		$charset		Optional: Character set (default: UTF-8)
	 *	@param		string		$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		self		Message object for chaining
	 */
	public function addHtml( string $content, ?string $charset = 'UTF-8', ?string $encoding = 'base64' ): self
	{
		return $this->addPart( new MessagePartHTML( $content, $charset, $encoding ) );
	}

	/**
	 *	Add image for HTML part by content.
	 *	Alias for addInlineImage.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$filePath		File Path of image to embed
	 *	@param		string		$mimeType		Optional: MIME type of file
	 *	@param		string		$encoding		Optional: Encoding to apply
	 *	@return		self		Message object for chaining
	 *	@deprecated	use addInlineImage instead
	 *	@todo		remove in 2.1
	 */
	public function addHtmlImage( $id, string $filePath, ?string $mimeType = NULL, ?string $encoding = NULL ): self
	{
		return $this->addInlineImage( $id, $filePath, $mimeType, $encoding );
	}

	/**
	 *	Add image for HTML part by content.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$filePath		File Path of image to embed
	 *	@param		string		$mimeType		Optional: MIME type of file
	 *	@param		string		$encoding		Optional: Encoding to apply
	 *	@return		self		Message object for chaining
	 */
	public function addInlineImage( $id, string $filePath, ?string $mimeType = NULL, ?string $encoding = NULL ): self
	{
		$part	= new MessagePartInlineImage( $id, $filePath, $mimeType, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	Add forwared mail part by plain content.
	 *	@access		public
	 *	@param		string		$content		Nested mail content to add as message part
	 *	@param		string		$charset		Optional: Character set (default: UTF-8)
	 *	@param		string		$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		self		Message object for chaining
	 */
	public function addMail( string $content, ?string $charset = 'UTF-8', ?string $encoding = 'base64' ): self
	{
		$part	= new MessagePartMail( $content, $charset, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	General way to add another mail part.
	 *	More specific: addText, addHtml, addHtmlImage, addAttachment.
	 *	@access		public
	 *	@param		MessagePart	$part		Part of mail
	 *	@return		self		Message object for chaining
	 */
	public function addPart( MessagePart $part ): self
	{
		$this->parts[]	= $part;
		return $this;
	}

	public function addRecipient( $participant, ?string $name = NULL, ?string $type = "To" ): self
	{
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
			$recipient	= MessageHeaderEncoding::encodeIfNeeded( $name ).' '.$recipient;
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

	public function addReplyTo( $participant, ?string $name = NULL ): self
	{
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
	 *	@return		self		Message object for chaining
	 */
	public function addText( string $content, ?string $charset = 'UTF-8', ?string $encoding = 'base64' ): self
	{
		$part	= new MessagePartText( $content, $charset, $encoding );
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
	public static function create(): self
	{
		return static::getInstance();
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable (deprecated)
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if given encoding is not supported
	 */
	static public function encodeIfNeeded( string $string, ?string $encoding = "base64", ?bool $fold = TRUE ){
		if( preg_match( "/^[\w\s\.-:#]+$/", $string ) )
			return $string;
		switch( strtolower( $encoding ) ){
			case 'base64':
				return "=?UTF-8?B?".base64_encode( $string )."?=";
			case 'quoted-printable':
				$quotedString	= quoted_printable_encode( $string );
				$quotedString	= str_replace( '='.Message::$delimiter, '', $quotedString );
				if( !$fold )
					return "=?UTF-8?Q?".$quotedString."?=";
				$lines	= str_split( $quotedString, Message::$lineLength );
				foreach( $lines as $nr => $string ){
					$string	= str_replace( '?', '=3F', $string );
					$string	= str_replace( ' ', '_', $string );
					$lines[$nr]	= "=?UTF-8?Q?".$string."?=";
				}
				return join( Message::$delimiter."\t", $lines );
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
	 *	@throws		\InvalidArgumentException	if given encoding is not supported
	 */
	static public function decodeIfNeeded( string $string, ?string $encoding = "base64" ): string
	{
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
	public function getAttachments( ?bool $withInlineImages = TRUE ): array
	{
		$list	= array();
		foreach( $this->parts as $part ){
			if( $part instanceof MessagePartAttachment )
				$list[]	= $part;
			if( $part instanceof MessagePartInlineImage )
				if( $withInlineImages )
					$list[]	= $part;
			if( $part instanceof MessagePartMail )
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
	public function getFiles( ?bool $withInlineImages = TRUE ): array
	{
		return $this->getAttachments( $withInlineImages );
	}

	/**
	 *	Returns set headers.
	 *	@access		public
	 *	@return		MessageHeaderSection
	 */
	public function getHeaders(): MessageHeaderSection
	{
		return $this->headers;
	}

	/**
	 *	Returns set or empty HTML part.
	 *	@access		public
	 *	@return		MessagePartHTML
	 */
	public function getHtml(): MessagePartHTML
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartHTML )
				return $part;
		return new MessagePartHtml( '' );
	}

	/**
	 *	Returns list inline images to be embeded with HTML.
	 *	@access		public
	 *	@return		array
	 */
	public function getInlineImages(): array
	{
		$list	= array();
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartInlineImage )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	static public function getInstance(): self
	{
		return new static;
	}

	/**
	 *	Returns list attached mails.
	 *	@access		public
	 *	@return		array
	 */
	public function getMails(): array
	{
		$list	= array();
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartMail )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Returns list of set body parts.
	 *	@access		public
	 *	@param		boolean		$withAttachments	Flag: return attachment parts also
	 *	@return		array
	 */
	public function getParts( ?bool $withAttachments = TRUE ): array
	{
		if( $withAttachments )
			return $this->parts;
		$list	= array();
		foreach( $this->parts as $part ){
			if( $part instanceof MessagePartAttachment )
				if( !$withAttachments)
					continue;
			if( $part instanceof MessagePartInlineImage )
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
	public function getRecipients( ?string $type = NULL )
	{
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
	 *	@return		Address
	 */
	public function getSender(): Address
	{
		return $this->sender;
	}

	/**
	 *	Returns set mail subject.
	 *	@access		public
	 *	@param		string|NULL		$encoding		Optional: Types: base64, quoted-printable. Default: none
	 *	@return		string
	 */
	public function getSubject( ?string $encoding = NULL ): string
	{
		return $this->subject;
	}

	/**
	 *	Returns set or empty text part.
	 *	@access		public
	 *	@return		MessagePartText
	 */
	public function getText(): MessagePartText
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartText )
				return $part;
		return new MessagePartText( '' );
	}

	/**
	 *	Returns mail user agent.
	 *	@access		public
	 *	@return		string
	 */
	public function getUserAgent(): string
	{
		return $this->userAgent;
	}

	/**
	 *	Indicates whether attachments are set.
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasAttachments(): bool
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartAttachment )
				return TRUE;
		return FALSE;
	}

	/**
	 *	Indicates whether an HTML part is set.
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasHTML(): bool
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartHTML )
				return TRUE;
		return FALSE;
	}

	/**
	 *	Indicates whether inline images are set.
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasInlineImages(): bool
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartInlineImage )
				return TRUE;
		return FALSE;
	}

	/**
	 *	Indicates whether mails where attached.
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasMails(): bool
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartMail )
				return TRUE;
		return FALSE;
	}

	/**
	 *	Indicates whether a text part is set.
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasText(): bool
	{
		foreach( $this->parts as $part )
			if( $part instanceof MessagePartText )
				return TRUE;
		return FALSE;
	}

	public function setReadNotificationRecipient( $participant, ?string $name = NULL ): self
	{
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
	 *	@param		string			$name			Optional: Mail sender name
	 *	@return		self			Message object for chaining
	 *	@throws		\InvalidArgumentException		if given participant is neither string nor instance of \CeusMedia\Mail\Address
	 */
	public function setSender( $participant, ?string $name = NULL ): self
	{
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
	 *	@return		self		Message object for chaining
	 */
	public function setSubject( string $subject ): self
	{
		$this->subject	= $subject;
		return $this;
	}

	/**
	 *	Sets mail user agent for mailer header.
	 *	@access		public
	 *	@param		string		$userAgent		Mailer user agent
	 *	@return		self		Message object for chaining
	 */
	public function setUserAgent( string $userAgent ): self
	{
		$this->userAgent = $userAgent;
		return $this;
	}
}
