<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Collector container for mails.
 *
 *	Copyright (c) 2007-2025 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use CeusMedia\Common\Exception\IO;
use CeusMedia\Mail\Address as Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Address\Collection\Parser as AddressCollectionParser;
use CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use CeusMedia\Mail\Message\Part\Text as MessagePartText;
use CeusMedia\Mail\Util\Str;

use DomainException;
use RangeException;
use Throwable;

use function array_reverse;
use function count;
use function in_array;
use function is_string;
use function strcasecmp;
use function strlen;
use function strtolower;
use function strtoupper;
use function trim;
use function ucfirst;

/**
 *	Collector container for mails
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Message
{
	/**	@var		string						$delimiter		Line separator, for some reason only \n must be possible */
	public static string $delimiter				= "\r\n";

	/**	@var		integer						$lineLength		Maximum line length of mail content */
	public static int $lineLength				= 75;

	/** @var		string|NULL					$version		Version of mail library when mail object has been created */
	public ?string $version						= NULL;

	/**	@var		MessagePart[]				$parts			List of mail parts */
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
		$this->userAgent	= Library::getUserAgent();
		$this->version		= Library::getVersion();
	}

	/**
	 *	Set user agent and version on wake up, if available and not set in object.
	 *	@return		void
	 */
	public function __wakeup()
	{
		if( !Str::hasContent( $this->userAgent ) )
			$this->setUserAgent( Library::getUserAgent() );
		if( !Str::hasContent( $this->version ) )
			if( NULL !== Library::getVersion() )
				$this->setVersion( Library::getVersion() );
	}

	/**
	 *	Static constructor.
	 *	@static
	 *	@access		public
	 *	@return		self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 *	@codeCoverageIgnore
	 */
	public static function create(): self
	{
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
	 *	@param		string			$type			Type of receiver (TO, CC, BCC), case-insensitive, default: TO
	 *	@return		self			Message object for chaining
	 */
	public function addRecipient( Address|string $address, string $name = NULL, string $type = "TO" ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( !in_array( strtoupper( $type ), [ "TO", "CC", "BCC" ], TRUE ) )
			throw new DomainException( 'Invalid recipient type' );

		if( Str::hasContent( $name ) )
			$address->setName( $name );

		$recipients	= $this->recipients[strtolower( $type )];
		$isKnown	= FALSE;
		foreach( $recipients->getAll() as $recipient ){
			if( 0 !== strcasecmp( $recipient->getAddress(), $address->getAddress() ) )
				continue;
			$isKnown	= TRUE;															//  same address only once
			$knownName	= trim( $recipient->getName( FALSE ) ?? '' );
			$newName	= trim( $address->getName( FALSE ) ?? '' );
			if( '' === $knownName && '' !== $newName )								//  name taken if missing
				$recipient->setName( $newName );
			break;
		}
		if( !$isKnown )
			$recipients->add( $address );
		//  header rendered again in any case: a known address object may have been changed outside

		//  one header per type, listing all addresses (RFC 5322, 3.6.3), BCC is not shown
		if( 'BCC' !== strtoupper( $type ) ){
			$fieldName	= ucfirst( strtolower( $type ) );
			$this->headers->setFieldPair( $fieldName, $this->renderAddressHeader( $fieldName, $recipients->getAll() ) );
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
	public function addReplyTo( Address|string $address, string $name = NULL ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( Str::hasContent( $name ) )
			$address->setName( $name );

		$addresses	= $this->getReplyTo() ?? [];
		foreach( $addresses as $replyTo )
			if( 0 === strcasecmp( $replyTo->getAddress(), $address->getAddress() ) )
				return $this;

		//  one header, listing all addresses (RFC 5322, 3.6.2); unreadable values (no usable address) are replaced
		$addresses[]	= $address;
		$this->headers->setFieldPair( 'Reply-To', $this->renderAddressHeader( 'Reply-To', $addresses ) );
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
	 *	@return		MessagePartAttachment[]
	 */
	public function getAttachments(): array
	{
		$list	= [];
		foreach( $this->parts as $part )
			if( $part->isAttachment() )
				/** @var MessagePartAttachment $part */
				$list[]	= $part;
		return $list;
	}

	/**
	 *	@return		Address[]
	 */
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
				/** @var MessagePartHTML $part */
				return $part;
		throw new RangeException( 'No HTML part assigned' );
	}

	/**
	 *	Returns list inline images to be embedded with HTML.
	 *	@access		public
	 *	@return		MessagePartInlineImage[]
	 */
	public function getInlineImages(): array
	{
		$list	= [];
		foreach( $this->parts as $part )
			if( $part->isInlineImage() )
				/** @var MessagePartInlineImage $part */
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
	 *	@return		MessagePartMail[]
	 */
	public function getMails(): array
	{
		$list	= [];
		foreach( $this->parts as $part )
			if( $part->isMail() )
				/** @var MessagePartMail $part */
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
	 *	Returns addresses to reply to, from Reply-To header (also several ones, as parsed from foreign messages).
	 *	Unreadable header values, e.g. of foreign messages, are skipped.
	 *	@access		public
	 *	@return		Address[]|NULL
	 */
	public function getReplyTo(): ?array
	{
		$parser		= AddressCollectionParser::getInstance();
		$encoder	= MessageHeaderEncoding::getInstance();
		$list		= [];
		foreach( $this->headers->getFieldsByName( 'Reply-To' ) as $field ){
			try{
				$value	= $encoder->decodeAddressListIfNeeded( $field->getValue() );			//  unfold, decode names, quoted if needed
				foreach( $parser->parse( $value )->getAll() as $address )
					$list[]	= $address;
			}
			catch( Throwable ){															//  unreadable, e.g. of foreign message
			}
		}
		return $list;
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
				/** @var MessagePartText $part */
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
	 *	Returns mail library version.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getVersion(): ?string
	{
		return $this->version;
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
	 *	Indicates whether at least one recipient is set, of any type (To, Cc or Bcc).
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasRecipients(): bool
	{
		foreach( $this->recipients as $collection )
			if( 0 !== count( $collection ) )
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
	 *	Removes a part by its index.
	 *	Attention: The index is based on call to getParts, not getAttachments etc.
	 *	@param		int		$nr		Index of part to remove
	 *	@return		static
	 */
	public function removePart( int $nr ): static
	{
		if( array_key_exists( $nr, $this->parts ) )
			unset( $this->parts[ $nr ] );
		return $this;
	}

	/**
	 *	Sets recipient of read notification (Disposition-Notification-To, RFC 8098), replacing a set one.
	 *	@access		public
	 *	@param		Address|string	$address		Address to send notification to
	 *	@param		string|NULL		$name			Additional name of address
	 */
	public function setReadNotificationRecipient( Address|string $address, string $name = NULL ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( Str::hasContent( $name ) )
			$address->setName( $name );
		$this->headers->setFieldPair( 'Disposition-Notification-To', $this->renderAddressHeader( 'Disposition-Notification-To', [$address] ) );
		return $this;
	}

	/**
	 *	Sets sender address and name.
	 *	@access		public
	 *	@param		Address|string	$address		Address object or string
	 *	@param		string|NULL		$name			Optional: Mail sender name
	 *	@return		self			Message object for chaining
	 */
	public function setSender( Address|string $address, string $name = NULL ): self
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		if( Str::hasContent( $name ) )
			$address->setName( $name );
		$this->sender	= $address;
		$this->headers->setFieldPair( 'From', $this->renderAddressHeader( 'From', [$address] ) );
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

	/**
	 *	@param		string		$version
	 *	@return		self
	 */
	public function setVersion( string $version ): self
	{
		$this->version	= $version;
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Renders addresses as value of a header field: names encoded or quoted, folded between addresses.
	 *	@access		protected
	 *	@param		string				$name			Header field name, for line length
	 *	@param		array<Address>		$addresses		Addresses to list
	 *	@return		string
	 */
	protected function renderAddressHeader( string $name, array $addresses ): string
	{
		return AddressCollectionRenderer::getInstance()
			->setEncodeNames()
			->setLineLength( MessageHeaderEncoding::LINE_LENGTH )
			->setOffset( strlen( $name.': ' ) )
			->render( new AddressCollection( $addresses ) );
	}
}
