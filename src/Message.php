<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Collector container for mails.
 *
 *	Copyright (c) 2007-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use CeusMedia\Common\Exception\IO;
use CeusMedia\Mail\Address as Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use CeusMedia\Mail\Message\Part\Text as MessagePartText;

use DomainException;
use InvalidArgumentException;
use RangeException;

use function array_reverse;
use function in_array;
use function is_a;
use function is_string;
use function strlen;
use function strtoupper;
use function trim;
use function ucfirst;

/**
 *	Collector container for mails
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Message
{
	/**	@var		string						$delimiter		Line separator, for some reasons only \n must be possible */
	public static string $delimiter				= "\r\n";

	/**	@var		integer						$lineLength		Maximum line length of mail content */
	public static int $lineLength				= 75;

	/**	@var		array						$parts			List of mail parts */
	protected array $parts						= [];

	/**	@var		MessageHeaderSection		$headers		Mail header section */
	protected MessageHeaderSection $headers;

	/**	@var		Address|null				$sender			Sender mail address */
	protected ?Address $sender					= NULL;

	/**	@var		array<string,AddressCollection>		$recipients		List of recipients */
	protected array $recipients					= [];

	/**	@var		string|NULL					$subject		Mail subject */
	protected ?string $subject					= NULL;

	/**	@var		string|NULL					$userAgent		Mailer agent aka user agent */
	protected ?string $userAgent				= NULL;

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
		$this->setUserAgent(Library::getUserAgent());
	}

	public function __wakeup()
	{
		if( NULL === $this->userAgent || 0 === strlen( trim( $this->userAgent ) ) )
			$this->setUserAgent(Library::getUserAgent());
	}

	/**
	 *	Static constructor.
	 *	@static
	 *	@access		public
	 *	@return		self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 *	@codeCoverageIgnore
	 *	@noinspection	PhpDocMissingThrowsInspection
	 */
	public static function create(): self
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method getInstance instead' );
		return new self();
	}

	/**
	 *	Add file as attachment part.
	 *	@access		public
	 *	@param		string			$filePath		Path of file to add
	 *	@param		string|NULL		$mimeType		Optional: MIME type of file
	 *	@param		string|NULL		$encoding		Optional: Encoding to apply
	 *	@param		string|NULL		$fileName		Optional: Name of file in part
	 *	@return		self			Message object for chaining
	 *	@throws		IO
	 *	@noinspection	PhpDocMissingThrowsInspection
	 */
	public function addAttachment( string $filePath, string $mimeType = NULL, string $encoding = NULL, string $fileName = NULL ): self
	{
		$part	= new MessagePartAttachment();
		$part->setFile( $filePath, $mimeType, $encoding, $fileName );
		return $this->addPart( $part );
	}

	/**
	 *	Adds a header.
	 *	@access		public
	 *	@param		MessageHeaderField	$field		Mail header field object
	 *	@return		self				Message object for chaining
	 */
	public function addHeader( MessageHeaderField $field ): self
	{
		$this->headers->addField( $field );
		return $this;
	}

	/**
	 *	Sets a header.
	 *	@access		public
	 *	@param		string			$key		Key of header
	 *	@param		string			$value		Value of header
	 *	@return		self			Message object for chaining
	 */
	public function addHeaderPair( string $key, string $value ): self
	{
		$field	= new MessageHeaderField( $key, $value );
		return $this->addHeader( $field );
	}

	/**
	 *	Add HTML part by content.
	 *	@access		public
	 *	@param		string			$content		HTML content to add as message part
	 *	@param		string			$charset		Optional: Character set (default: UTF-8)
	 *	@param		string			$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		self			Message object for chaining
	 */
	public function addHTML( string $content, string $charset = 'UTF-8', string $encoding = 'base64' ): self
	{
		return $this->addPart( new MessagePartHTML( $content, $charset, $encoding ) );
	}

	/**
	 *	Add image for HTML part by content.
	 *	@access		public
	 *	@param		string			$id				Content ID of image to be used in HTML part
	 *	@param		string			$filePath		File Path of image to embed
	 *	@param		string|NULL		$mimeType		Optional: MIME type of file
	 *	@param		string|NULL		$encoding		Optional: Encoding to apply
	 *	@param		string|NULL		$fileName		Optional: Name of file in part
	 *	@return		self			Message object for chaining
	 *	@throws		IO
	 */
	public function addInlineImage( string $id, string $filePath, string $mimeType = NULL, string $encoding = NULL, string $fileName = NULL ): self
	{
		$part	= new MessagePartInlineImage( $id );
		$part->setFile( $filePath, $mimeType, $encoding, $fileName );
		return $this->addPart( $part );
	}

	/**
	 *	Add forwarded mail part by plain content.
	 *	@access		public
	 *	@param		string			$content		Nested mail content to add as message part
	 *	@param		string			$charset		Optional: Character set (default: UTF-8)
	 *	@param		string			$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		self			Message object for chaining
	 */
	public function addMail( string $content, string $charset = 'UTF-8', string $encoding = 'base64' ): self
	{
		$part	= new MessagePartMail( $content, $charset, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	General way to add another mail part.
	 *	More specific: addText, addHTML, addAttachment, addInlineImage, addMail.
	 *	@access		public
	 *	@param		MessagePart		$part		Part of mail
	 *	@return		self			Message object for chaining
	 */
	public function addPart( MessagePart $part ): self
	{
		$this->parts[]	= $part;
		return $this;
	}

	/**
	 *	Add a receiver as string to address object.
	 *	@access		public
	 *	@param		Address|string	$address		Address string or object to add as receiver
	 *	@param		string|NULL		$name			Additional name of the address
	 *	@param		string			$type			Type of receiver (TO, CC, BCC), case insensitive, default: TO
	 *	@return		self			Message object for chaining
	 */
	public function addRecipient( $address, string $name = NULL, string $type = "TO" ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( !is_a( $address, Address::class ) )
			throw new InvalidArgumentException( 'Invalid value of first argument' );
		if( !in_array( strtoupper( $type ), [ "TO", "CC", "BCC" ], TRUE ) )
			throw new DomainException( 'Invalid recipient type' );

		$encoder	= MessageHeaderEncoding::getInstance();

		if( NULL !== $name && 0 !== strlen( trim( $name ) ) )
			$address->setName( $name );
		$this->recipients[strtolower( $type )]->add( $address );

		$recipient	= '<'.$address->getAddress().'>';
		if( NULL !== $name && 0 !== strlen( trim( $name ) ) )
			$recipient	= $encoder->encodeIfNeeded( $name ).' '.$recipient;
		$recipient	= $address->get();

		if( 'BCC' !== strtoupper( $type ) ){
			$fields	= $this->headers->getFieldsByName( $type );
			foreach( $fields as $field )
				if( $field->getValue() == $recipient )
					return $this;
			$this->addHeaderPair( ucfirst( strtolower( $type ) ), $recipient );
		}
		return $this;
	}

	/**
	 *	Adds Reply-To header to message.
	 *	@access		public
	 *	@param		Address|string	$address		Address to reply to
	 *	@param		string|NULL		$name			Additional name of address
	 *	@return		self
	 */
	public function addReplyTo( $address, string $name = NULL ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( !is_a( $address, Address::class ) )
			throw new InvalidArgumentException( 'Invalid value of first argument' );
		if( NULL !== $name && 0 !== strlen( trim( $name ) ) )
			$address->setName( $name );
		$this->addHeaderPair( 'Reply-To', $address->get() );
		return $this;
	}

	/**
	 *	Add plaintext part by content.
	 *	@access		public
	 *	@param		string			$content		Plaintext content to add as message part
	 *	@param		string			$charset		Optional: Character set (default: UTF-8)
	 *	@param		string			$encoding		Optional: Encoding to apply (default: base64)
	 *	@return		self			Message object for chaining
	 */
	public function addText( string $content, string $charset = 'UTF-8', string $encoding = 'base64' ): self
	{
		$part	= new MessagePartText( $content, $charset, $encoding );
		return $this->addPart( $part );
	}

	/**
	 *	Returns list of set attachment parts.
	 *	@access		public
	 *	@return		array
	 */
	public function getAttachments(): array
	{
		$list	= [];
		foreach( $this->parts as $part )
			if( $part->isAttachment() )
				$list[]	= $part;
		return $list;
	}

	public function getDeliveryChain(): array
	{
		$list	= [];
		foreach( $this->headers->getFieldsByName( 'X-Original-To' ) as $field ){
			$list[]	= new Address( $field->getValue() );
		}
		return array_reverse( $list );
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
	 *	Returns set HTML part.
	 *	@access		public
	 *	@return		MessagePartHTML
	 *	@throws		RangeException		if no HTML part is available
	 */
	public function getHTML(): MessagePartHTML
	{
		foreach( $this->parts as $part )
			if( $part->isHTML() )
				return $part;
		throw new RangeException( 'No HTML part assigned' );
	}

	/**
	 *	Returns list inline images to be embedded with HTML.
	 *	@access		public
	 *	@return		array
	 */
	public function getInlineImages(): array
	{
		$list	= [];
		foreach( $this->parts as $part )
			if( $part->isInlineImage() )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 *	Returns list of attached mails.
	 *	@access		public
	 *	@return		array
	 */
	public function getMails(): array
	{
		$list	= [];
		foreach( $this->parts as $part )
			if( $part->isMail() )
				$list[]	= $part;
		return $list;
	}

	/**
	 *	Returns list of set body parts.
	 *	@access		public
	 *	@param		boolean		$withAttachments	Flag: return attachment parts also
	 *	@param		boolean		$withInlineImages	Flag: include inline images, default: yes
	 *	@param		boolean		$withMails			Flag: include attached mails, default: yes
	 *	@return		array
	 */
	public function getParts( bool $withAttachments = TRUE, bool $withInlineImages = TRUE, bool $withMails = TRUE ): array
	{
		if( $withAttachments && $withInlineImages && $withMails  )
			return $this->parts;
		$list	= [];
		foreach( $this->parts as $part ){
			if( !$withAttachments && $part->isAttachment() )
				continue;
			else if( !$withInlineImages && $part->isInlineImage() )
				continue;
			else if( !$withMails && $part->isMail() )
				continue;
			$list[]	= $part;
		}
		return $list;
	}

	/**
	 *	Returns list of set recipient addresses grouped by type.
	 *	@access		public
	 *	@return		array
	 */
	public function getRecipients(): array
	{
		return $this->recipients;
	}

	/**
	 *	Returns set recipient addresses.
	 *	@access		public
	 *	@param		string			$type		One of {TO, CC, BCC}
	 *	@return		AddressCollection
	 */
	public function getRecipientsByType( string $type = 'TO' ): AddressCollection
	{
		if( !in_array( strtoupper( $type ), [ 'TO', 'CC', 'BCC' ], TRUE ) )
			throw new DomainException( 'Type must be of to, cc or bcc' );
		return $this->recipients[strtolower( $type )];
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		Address[]|NULL
	 */
	public function getReplyTo(): ?array
	{
		return array_map(static function( Message\Header\Field $field ){
			return new Address( $field->getValue() );
		}, $this->headers->getFieldsByName( 'Reply-To' ) );
	}

	/**
	 *	Returns assigned mail sender.
	 *	@access		public
	 *	@return		Address|NULL
	 */
	public function getSender(): ?Address
	{
		return $this->sender;
	}

	/**
	 *	Returns set mail subject.
	 *	@access		public
	 *	@param		string|NULL		$encoding		Optional: Types: base64, quoted-printable. Default: none
	 *	@return		string|NULL
	 */
	public function getSubject( string $encoding = NULL ): ?string
	{
		return $this->subject;
	}

	/**
	 *	Returns set text part.
	 *	@access		public
	 *	@return		MessagePartText
	 *	@throws		RangeException		if no text part is available
	 */
	public function getText(): MessagePartText
	{
		foreach( $this->parts as $part )
			if( $part->isText() )
				return $part;
		throw new RangeException( 'No text part assigned' );
	}

	/**
	 *	Returns mail user agent.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getUserAgent(): ?string
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
			if( $part->isAttachment() )
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
			if( $part->isHTML() )
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
			if( $part->isInlineImage() )
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
			if( $part->isMail() )
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
			if( $part->isText() )
				return TRUE;
		return FALSE;
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		Address|string	$address		Address to send notification to
	 *	@param		string|NULL		$name			Additional name of address
	 */
	public function setReadNotificationRecipient( $address, string $name = NULL ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( NULL !== $name && 0 !== strlen( trim( $name ) ) )
			$address->setName( $name );
		$this->headers->addFieldPair( 'Disposition-Notification-To', $address->get() );
		return $this;
	}

	/**
	 *	Sets sender address and name.
	 *	@access		public
	 *	@param		Address|string	$address		Address object or string
	 *	@param		string|NULL		$name			Optional: Mail sender name
	 *	@return		self			Message object for chaining
	 *	@throws		InvalidArgumentException		if given address is neither string nor instance of \CeusMedia\Mail\Address
	 */
	public function setSender( $address, string $name = NULL ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( !is_a( $address, "\CeusMedia\Mail\Address" ) )
			throw new InvalidArgumentException( 'Invalid value of first argument' );
		if( NULL !== $name && 0 !== strlen( trim( $name ) ) )
			$address->setName( $name );
		$this->sender	= $address;
		$this->headers->removeFieldByName( 'From' );
		$this->addHeaderPair( "From", $address->get() );
		return $this;
	}

	/**
	 *	Sets Mail Subject.
	 *	@access		public
	 *	@param		string			$subject	Subject of Mail
	 *	@return		self			Message object for chaining
	 */
	public function setSubject( string $subject ): self
	{
		$this->subject	= $subject;
		return $this;
	}

	/**
	 *	Sets mail user agent for mailer header.
	 *	@access		public
	 *	@param		string			$userAgent		Mailer user agent
	 *	@return		self			Message object for chaining
	 */
	public function setUserAgent( string $userAgent ): self
	{
		$this->userAgent = $userAgent;
		return $this;
	}
}
