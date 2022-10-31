<?php
declare(strict_types=1);

/**
 *	Abstract Mail Part.
 *
 *	Copyright (c) 2007-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use CeusMedia\Mail\Message;
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
use function file_exists;
use function finfo_close;
use function finfo_file;
use function finfo_open;
use function function_exists;
use function imap_8bit;
use function imap_qprint;
use function in_array;
use function mb_convert_encoding;
use function quoted_printable_decode;
use function quoted_printable_encode;
use function strlen;
use function strtolower;
use function rtrim;

/**
 *	Abstract Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
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

	/**	@var	string			$charset		Character set */
	protected string $charset;

	/**	@var	string|NULL		$content		Content */
	protected ?string $content	= NULL;

	/**	@var	string			$encoding		Encoding */
	protected string $encoding;

	/**	@var	string			$format			Format */
	protected string $format;

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
			case '':
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
	 *	Return string represenation of this mail path, so with headers and content.
	 *	Every part implements this abstract method.
	 *	@abstract
	 *	@param		integer						$sections				Section(s) to render, default: all
	 *	@param		MessageHeaderSection|NULL	$additionalHeaders		Section with header fields to render aswell
	 *	@return		string
	 */
	abstract public function render( int $sections = self::SECTION_ALL, ?MessageHeaderSection $additionalHeaders = NULL ): string;

	/**
	 *	Set character set.
	 *	@access		public
	 *	@param		string		$charset			Character set to set
	 *	@return		self
	 */
	public function setCharset( $charset ): self
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
	public function setContent( $content ): self
	{
		$this->content	= $content;
		return $this;
	}

	/**
	 *	Set encoding.
	 *	@access		public
	 *	@param		string		$encoding		Encoding (7bit,8bit,base64,quoted-printable,binary)
	 *	@return		self
	 *	@throws		InvalidArgumentException	if encodfgets(ing is invalid
	 */
	public function setEncoding( $encoding ): self
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
	public function setFormat( $format ): self
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
	public function setMimeType( $mimeType ): self
	{
		$this->mimeType	= $mimeType;
		return $this;
	}

	//  --  PROTECTED  -- //

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
				$result	= @mb_convert_encoding( $content, 'UTF-8', strtolower( $encoding ) );
				/** @var string|FALSE $result */
				if( FALSE === $result )
					$result	= mb_convert_encoding( $content, 'UTF-8', '8bit' );
				/** @var string|FALSE $result */
				if( FALSE !== $result )
					$content	= $result;
				if( $split && strlen( $content ) > $lineLength )
					$content	= static::wrapContent( $content, $lineLength, $delimiter );
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
	 *	Get MIME type of a file by its file path.
	 *	@access		protected
	 *	@param		string		$filePath		Path of file to get MIME type of
	 *	@return		string|NULL
	 */
	protected function getMimeTypeFromFile( string $filePath ): ?string
	{
		if( !file_exists( $filePath ) )
			throw new InvalidArgumentException( 'File "'.$filePath.'" is not existing' );
		$finfo	= finfo_open( FILEINFO_MIME_TYPE );
		if( FALSE === $finfo )
			throw new RuntimeException( 'fileinfo is not available' );
		$type	= finfo_file( $finfo, $filePath );
		finfo_close( $finfo );
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
		$delimiter	= $delimiter ?? Message::$delimiter;
		$lineLength	= max( 1, $length ?? Message::$lineLength );
		$content	= chunk_split( $content, $lineLength, $delimiter );
		return rtrim( $content, $delimiter );
	}
}
