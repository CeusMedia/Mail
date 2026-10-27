<?php
declare(strict_types=1);

/**
 *	Abstract Mail Part.
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
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use CeusMedia\Mail\Message\Part\Text as MessagePartText;

use InvalidArgumentException;
use RuntimeException;

use function base64_decode;
use function chunk_split;
use function date;
use function explode;
use function file_exists;
use function finfo_close;
use function finfo_file;
use function finfo_open;
use function function_exists;
use function imap_8bit;
use function imap_qprint;
use function implode;
use function in_array;
use function join;
use function max;
use function mb_convert_encoding;
use function mb_strcut;
use function preg_match;
use function preg_replace;
use function quoted_printable_decode;
use function quoted_printable_encode;
use function strlen;
use function strtolower;
use function substr;
use function rtrim;
use function trim;

/**
 *	Abstract Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
abstract class Part
{
	public const TYPE_UNKNOWN			= 0;
	public const TYPE_TEXT				= 1;
	public const TYPE_MAIL				= 2;
	public const TYPE_ATTACHMENT		= 3;
	public const TYPE_HTML				= 4;
	public const TYPE_INLINE_IMAGE		= 5;

	public const TYPES					= [
		self::TYPE_UNKNOWN,
		self::TYPE_TEXT,
		self::TYPE_MAIL,
		self::TYPE_ATTACHMENT,
		self::TYPE_HTML,
		self::TYPE_INLINE_IMAGE,
	];

	public const SECTION_NONE			= 0;
	public const SECTION_ALL			= 1;
	public const SECTION_HEADER			= 2;
	public const SECTION_CONTENT		= 4;

	public const SECTIONS				= [
		self::SECTION_NONE,
		self::SECTION_ALL,
		self::SECTION_HEADER,
		self::SECTION_CONTENT,
	];

	/**	@var	integer		MAX_LINE_LENGTH		Maximum octets of a line without encoding (7bit, 8bit), CRLF excluded (RFC 5322, 2.1.1) */
	public const MAX_LINE_LENGTH		= 998;

	/**	@var	string|NULL		$charset		Character set */
	protected ?string $charset	= NULL;

	/**	@var	string|NULL		$content		Content */
	protected ?string $content	= NULL;

	/**	@var	string			$encoding		Encoding */
	protected string $encoding;

	/**	@var	string			$format			Format */
	protected string $format    = 'fixed';

	/**	@var	string			$mimeType		MIME type */
	protected string $mimeType;

	/**	@var	integer			$type			Detected type of part */
	protected int $type			= self::TYPE_UNKNOWN;

	/**
	 *	Convert content to UTF-8.
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Content to be decoded
	 *	@param		string		$encoding		Encoding (7bit,8bit,base64,quoted-printable,binary)
	 *	@param		string		$charset		Target character set, default: UTF-8
	 *	@return		string
	 *	@throws		InvalidArgumentException	if encoding is invalid
	 */
	public static function decodeContent( string $content, string $encoding, string $charset = 'UTF-8' ): string
	{
		switch( strtolower( $encoding ) ){
			case '7bit':
				/** @var string|FALSE $result */
				$result	= mb_convert_encoding( $content, 'UTF-8', '8bit' );
				if( FALSE === $result )
					throw new RuntimeException( 'Decoding 7bit content failed' );
				$content	= $result;
				break;
			case '8bit':
			case '':
				break;
			case 'base64':
			case 'binary':
				$result	= base64_decode( $content, TRUE );
				if( FALSE === $result )
					throw new RuntimeException( 'Encoded content contains invalid characters' );
				$content	= $result;
				break;
			case 'quoted-printable':
				if( function_exists( 'imap_qprint' ) )
					$result	= imap_qprint( $content );
				else
					$result	= quoted_printable_decode( $content );
				if( FALSE === $result )
					throw new RuntimeException( 'Decoding quoted-printable content failed' );
				$content	= $result;
				break;
			default:
				throw new InvalidArgumentException( 'Encoding method "'.$encoding.'" is not supported' );
		}
		return $content;
	}

	/**
	 *	Get set character set.
	 *	@access		public
	 *	@return		string|NULL		Set character set
	 */
	public function getCharset(): ?string
	{
		return $this->charset;
	}

	/**
	 *	Get set part content.
	 *	@access		public
	 *	@return		string|NULL		Set part content
	 */
	public function getContent(): ?string
	{
		return $this->content;
	}

	/**
	 *	Get set encoding.
	 *	@access		public
	 *	@return		string|NULL		Set encoding (7bit,8bit,base64,quoted-printable,binary)
	 */
	public function getEncoding(): ?string
	{
		return $this->encoding;
	}

	/**
	 *	Get set format.
	 *	@access		public
	 *	@return		string|NULL		Set format (fixed,flowed)
	 */
	public function getFormat(): ?string
	{
		return $this->format;
	}

	/**
	 *	Get set MIME type.
	 *	@access		public
	 *	@return		string|NULL		Set MIME type
	 */
	public function getMimeType(): ?string
	{
		return $this->mimeType;
	}

	/**
	 *	Returns type of part as identifier defined by constants.
	 *	On the first run, detection will be used to set found type.
	 *	All following calls will return detected value.
	 *	@access		public
	 *	@param		boolean			$forceDetection		Flag: get type by detection, default: no
	 *	@return		integer			Type of part as identifier defined by constants
	 */
	public function getType( bool $forceDetection = FALSE ): int
	{
		if( self::TYPE_UNKNOWN === $this->type || $forceDetection ){
			if( $this instanceof MessagePartInlineImage )
				$this->type	= static::TYPE_INLINE_IMAGE;
			else if( $this instanceof MessagePartMail )
				$this->type	= static::TYPE_MAIL;
			else if( $this instanceof MessagePartAttachment )
				$this->type	= static::TYPE_ATTACHMENT;
			else if( $this instanceof MessagePartHTML )
				$this->type	= static::TYPE_HTML;
			else if( $this instanceof MessagePartText )
				$this->type	= static::TYPE_TEXT;
		}
		return $this->type;
	}

	/**
	 *	Indicates whether this part is an attachment.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isAttachment(): bool
	{
		return $this->isOfType( static::TYPE_ATTACHMENT );
	}

	/**
	 *	Indicates whether this part is HTML.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isHTML(): bool
	{
		return $this->isOfType( static::TYPE_HTML );
	}

	/**
	 *	Indicates whether this part is an inline image.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isInlineImage(): bool
	{
		return $this->isOfType( static::TYPE_INLINE_IMAGE );
	}

	/**
	 *	Indicates whether this part is a mail.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isMail(): bool
	{
		return $this->isOfType( static::TYPE_MAIL );
	}

	/**
	 *	Indicates whether the type of this part matches an identifier defined by constants.
	 *	Identifier is integer value of part constants TYPE_*.
	 *	Constants: TYPE_TEXT, TYPE_HTML, TYPE_ATTACHMENT, TYPE_MAIL, TYPE_INLINE_IMAGE
	 *	@access		public
	 *	@param		integer		$type			Type identifier defined by constants to check for
	 *	@return		boolean
	 *	@example	isOfType(Part::TYPE_HTML)
	 */
	public function isOfType( int $type ): bool
	{
		return $this->getType() === $type;
	}

	/**
	 *	Indicates whether this part is a text.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isText(): bool
	{
		return $this->isOfType( static::TYPE_TEXT );
	}

	/**
	 *	Sets content headers of this part in given header section, e.g. Content-Type.
	 *	Default: by rendering the header section, parts of this library set them without rendering.
	 *	@access		public
	 *	@param		MessageHeaderSection	$section		Header section to set content headers in
	 *	@return		MessageHeaderSection	Given header section
	 */
	public function applyHeaders( MessageHeaderSection $section ): MessageHeaderSection
	{
		$this->render( self::SECTION_HEADER, $section );
		return $section;
	}

	/**
	 *	Return string representation of this mail path, so with headers and content.
	 *	Every part implements this abstract method.
	 *	@abstract
	 *	@param		integer						$sections				Section(s) to render, default: all
	 *	@param		MessageHeaderSection|NULL	$additionalHeaders		Section with header fields to render as well
	 *	@return		string
	 */
	abstract public function render( int $sections = self::SECTION_ALL, ?MessageHeaderSection $additionalHeaders = NULL ): string;

	/**
	 *	Set character set.
	 *	@access		public
	 *	@param		string		$charset			Character set to set
	 *	@return		self
	 */
	public function setCharset( string $charset ): self
	{
		$this->charset	= $charset;
		return $this;
	}

	/**
	 *	Set content.
	 *	@access		public
	 *	@param		string		$content			Content to set
	 *	@return		self
	 */
	public function setContent( string $content ): self
	{
		$this->content	= $content;
		return $this;
	}

	/**
	 *	Set encoding.
	 *	@access		public
	 *	@param		string		$encoding		Encoding (7bit,8bit,base64,quoted-printable,binary)
	 *	@return		self
	 *	@throws		InvalidArgumentException	if encoding is invalid
	 */
	public function setEncoding( string $encoding ): self
	{
		$encodings	= [ '', '7bit', '8bit', 'base64', 'quoted-printable', 'binary' ];
		if( !in_array( $encoding, $encodings, TRUE ) )
			throw new InvalidArgumentException( 'Invalid encoding: '.$encoding );
		$this->encoding	= $encoding;
		return $this;
	}

	/**
	 *	Set format.
	 *	@access		public
	 *	@param		string		$format			Format (fixed,flowed)
	 *	@return		self
	 *	@throws		InvalidArgumentException	if format is invalid
	 */
	public function setFormat( string $format ): self
	{
		$formats	= [ 'fixed', 'flowed' ];
		if( !in_array( $format, $formats, TRUE ) )
			throw new InvalidArgumentException( 'Invalid format' );
		$this->format	= $format;
		return $this;
	}

	/**
	 *	Set MIME type.
	 *	@access		public
	 *	@param		string		$mimeType			MIME type to set
	 *	@return		self
	 */
	public function setMimeType( string $mimeType ): self
	{
		$this->mimeType	= $mimeType;
		return $this;
	}

	//  --  PROTECTED  -- //

	/**
	 *	Renders Content-Disposition header value of a file part, parameters folded one per line.
	 *	@access		protected
	 *	@static
	 *	@param		string			$type			Disposition type: attachment or inline
	 *	@param		string|NULL		$fileName		File name, encoded if needed (RFC 2231)
	 *	@param		int|NULL		$size			File size in bytes
	 *	@param		int|NULL		$readTime		Timestamp of last access
	 *	@param		int|NULL		$creationTime	Timestamp of creation
	 *	@param		int|NULL		$modificationTime	Timestamp of last modification
	 *	@return		string
	 *	@see		https://datatracker.ietf.org/doc/html/rfc2183
	 */
	protected static function renderDisposition( string $type, ?string $fileName, ?int $size = NULL, ?int $readTime = NULL, ?int $creationTime = NULL, ?int $modificationTime = NULL ): string
	{
		$parameters	= [$type];
		if( NULL !== $fileName && '' !== $fileName )
			$parameters[]	= MessageHeaderEncoding::renderParameter( 'filename', $fileName );
		if( NULL !== $size )
			$parameters[]	= 'size='.$size;
		if( NULL !== $readTime )
			$parameters[]	= 'read-date="'.date( 'r', $readTime ).'"';
		if( NULL !== $creationTime )
			$parameters[]	= 'creation-date="'.date( 'r', $creationTime ).'"';
		if( NULL !== $modificationTime )
			$parameters[]	= 'modification-date="'.date( 'r', $modificationTime ).'"';
		return join( ';'.Message::$delimiter.' ', $parameters );
	}

	/**
	 *	Returns Content-Type value of file part: MIME type and file name, if set.
	 *	The name is set here also (besides Content-Disposition), for clients reading only this one.
	 *	@access		protected
	 *	@param		string|NULL	$fileName		File name, encoded if needed (RFC 2231)
	 *	@return		string
	 */
	protected function renderFileContentType( ?string $fileName ): string
	{
		if( NULL === $fileName || '' === $fileName )
			return $this->mimeType;
		return $this->mimeType.';'.Message::$delimiter.' '.MessageHeaderEncoding::renderParameter( 'name', $fileName );
	}

	/**
	 *	Returns Content-Type value of text part: MIME type, charset and format.
	 *	@access		protected
	 *	@return		string
	 */
	protected function renderTextContentType(): string
	{
		return join( '; ', [
			$this->mimeType,
			'charset="'.strtolower( trim( $this->charset ?? '' ) ).'"',
			'format='.$this->format
		] );
	}

	/**
	 *	Applies encoding to UTF-8 content.
	 *	@access		protected
	 *	@static
	 *	@param		string|NULL	$content		Content to be encode
	 *	@param		string		$encoding		Encoding (7bit,8bit,base64,quoted-printable,binary)
	 *	@param		boolean		$split			Flag: ... (default: yes)
	 *	@return		string
	 *	@throws		InvalidArgumentException	if encoding is invalid
	 */
	protected static function encodeContent( ?string $content, string $encoding, bool $split = TRUE ): string
	{
		if( NULL === $content )
			return '';
		$delimiter	= Message::$delimiter;
		$lineLength	= Message::$lineLength;
		switch( strtolower( $encoding ) ){
			case '7bit':
			case '8bit':
				//  content is sent as it is, already in the part's charset: only line breaks must be CRLF (RFC 2045, 2.7/2.8)
				$content	= (string) preg_replace( '/\r\n|\r|\n/', $delimiter, $content );
				if( $split )
					$content	= static::wrapLongLines( $content, static::MAX_LINE_LENGTH, $delimiter );
				break;
			case 'base64':
			case 'binary':
				$content	= base64_encode( $content );
				if( $split && strlen( $content ) > $lineLength )
					$content	= static::wrapContent( $content, $lineLength, $delimiter );
				break;
			case 'quoted-printable':
				if( function_exists( 'imap_8bit' ) )
					$result	= imap_8bit( $content );
				else
					$result	= quoted_printable_encode( $content );
				if( FALSE === $result )
					throw new RuntimeException( 'Decoding quoted-printable content failed' );
				$content	= $result;
				break;
			case '':
				break;
			default:
				throw new InvalidArgumentException( 'Encoding method "'.$encoding.'" is not supported' );
		}
		return $content;
	}

	/**
	 *	Returns transfer encoding to apply: set encoding, but quoted-printable for 7bit content containing non-ASCII,
	 *	which would be sent as 8bit data otherwise, labelled as 7bit (RFC 2045, 2.7).
	 *	@access		protected
	 *	@return		string
	 */
	protected function getTransferEncoding(): string
	{
		if( '7bit' === strtolower( $this->encoding ) && 1 === preg_match( '/[\x80-\xFF]/', $this->content ?? '' ) )
			return 'quoted-printable';
		return $this->encoding;
	}

	/**
	 *	Get MIME type of file by its file path.
	 *	@access		protected
	 *	@param		string		$filePath		Path of file to get MIME type of
	 *	@return		string|NULL
	 */
	protected function getMimeTypeFromFile( string $filePath ): ?string
	{
		if( !file_exists( $filePath ) )
			throw new InvalidArgumentException( 'File "'.$filePath.'" is not existing' );
		$fileInfo	= finfo_open( FILEINFO_MIME_TYPE );
		if( FALSE === $fileInfo )
			throw new RuntimeException( 'fileinfo is not available' );
		$type	= finfo_file( $fileInfo, $filePath );
		finfo_close( $fileInfo );
		return FALSE !== $type ? $type : NULL;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string			$content		Content to wrap
	 *	@param		integer|NULL	$length			...
	 *	@param		string|NULL		$delimiter		Content to wrap
	 *	@return		string
	 */
	protected static function wrapContent( string $content, ?int $length = NULL, ?string $delimiter = NULL ): string
	{
		$length		??= Message::$lineLength;
		$delimiter	??= Message::$delimiter;
		$content	= chunk_split( $content, max( 1, $length ), $delimiter );
		return rtrim( $content, $delimiter );
	}

	/**
	 *	Breaks lines longer than the given number of octets, without splitting a UTF-8 character.
	 *	Shorter lines stay unchanged, since an inserted line break changes the content of an unencoded part.
	 *	@access		protected
	 *	@static
	 *	@param		string			$content		Content with lines separated by delimiter
	 *	@param		integer			$length			Maximum octets per line
	 *	@param		string			$delimiter		Line separator
	 *	@return		string
	 *	@throws		InvalidArgumentException	if delimiter is empty
	 */
	protected static function wrapLongLines( string $content, int $length, string $delimiter ): string
	{
		if( '' === $delimiter )
			throw new InvalidArgumentException( 'Line delimiter must not be empty' );
		$lines	= [];
		foreach( explode( $delimiter, $content ) as $line ){
			while( strlen( $line ) > $length ){
				$chunk		= mb_strcut( $line, 0, $length, 'UTF-8' );
				if( '' === $chunk )												//  no UTF-8 character boundary found, cut by octets
					$chunk	= substr( $line, 0, $length );
				$lines[]	= $chunk;
				$line		= substr( $line, strlen( $chunk ) );
			}
			$lines[]	= $line;
		}
		return implode( $delimiter, $lines );
	}
}
