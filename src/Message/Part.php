<?php
/**
 *	Abstract Mail Part.
 *
 *	Copyright (c) 2007-2020 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use \CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use \CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use \CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use \CeusMedia\Mail\Message\Part\Text as MessagePartText;

/**
 *	Abstract Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
abstract class Part
{
	const TYPE_UNKNOWN			= 0;
	const TYPE_TEXT				= 1;
	const TYPE_MAIL				= 2;
	const TYPE_ATTACHMENT		= 3;
	const TYPE_HTML				= 4;
	const TYPE_INLINE_IMAGE		= 5;

	/**	@var	string			$charset		Character set */
	protected $charset;

	/**	@var	string			$content		Content */
	protected $content;

	/**	@var	string			$encoding		Encoding */
	protected $encoding;

	/**	@var	string			$format			Format */
	protected $format;

	/**	@var	string			$mimeType		MIME type */
	protected $mimeType;

	/**	@var	integer			$type			Detected type of part */
	protected $type				= 0;

	/**
	 *	Convert content to UTF-8.
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Content to be decoded
	 *	@param		string		$encoding		Encoding (7bit,8bit,base64,quoted-printable,binary)
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if encoding is invalid
	 */
	public static function decodeContent( $content, $encoding ): string
	{
		switch( strtolower( $encoding ) ){
			case '7bit':
			case '8bit':
				$content	= mb_convert_encoding( $content, strtolower( $encoding ), 'UTF-8' );
				break;
			case 'base64':
			case 'binary':
				$content	= base64_decode( $content );
				break;
			case 'quoted-printable':
				if( function_exists( 'imap_qprint' ) )
					$content	= imap_qprint( $content );
				else
					$content	= quoted_printable_decode( $content );
				break;
			case '':
				break;
			default:
				throw new \InvalidArgumentException( 'Encoding method "'.$encoding.'" is not supported' );
		}
		return $content;
	}

	/**
	 *	Get set character set.
	 *	@access		public
	 *	@return		string		Set character set
	 */
	public function getCharset(): string
	{
		return $this->charset;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 *	Get set encoding.
	 *	@access		public
	 *	@return		string		Set encoding (7bit,8bit,base64,quoted-printable,binary)
	 */
	public function getEncoding(): string
	{
		return $this->encoding;
	}

	/**
	 *	Get set format.
	 *	@access		public
	 *	@return		string		Set format (fixed,flowed)
	 */
	public function getFormat(): string
	{
		return $this->format;
	}

	/**
	 *	Get set MIME type.
	 *	@access		public
	 *	@return		string		Set MIME type
	 */
	public function getMimeType(): string
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
	public function getType( $forceDetection = FALSE ): int
	{
		if( !$this->type || $forceDetection ){
			if( $this instanceof MessagePartText )
				$this->type	= static::TYPE_TEXT;
			if( $this instanceof MessagePartHTML )
				$this->type	= static::TYPE_HTML;
			if( $this instanceof MessagePartAttachment )
				$this->type	= static::TYPE_ATTACHMENT;
			if( $this instanceof MessagePartInlineImage )
				$this->type	= static::TYPE_INLINE_IMAGE;
			if( $this instanceof MessagePartMail )
				$this->type	= static::TYPE_MAIL;
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

	abstract public function render( $headers = NULL ): string;

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
	 *	@throws		\InvalidArgumentException	if encoding is invalid
	 */
	public function setEncoding( $encoding ): self
	{
		$encodings	= array( '', '7bit', '8bit', 'base64', 'quoted-printable', 'binary' );
		if( !in_array( $encoding, $encodings ) )
			throw new \InvalidArgumentException( 'Invalid encoding: '.$encoding );
		$this->encoding	= $encoding;
		return $this;
	}

	/**
	 *	Set format.
	 *	@access		public
	 *	@param		string		$format			Format (fixed,flowed)
	 *	@return		self
	 *	@throws		\InvalidArgumentException	if format is invalid
	 */
	public function setFormat( $format ): self
	{
		$formats	= array( 'fixed', 'flowed' );
		if( !in_array( $format, $formats ) )
			throw new \InvalidArgumentException( 'Invalid format' );
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
	 *	@param		string		$content		Content to be encode
	 *	@param		string		$encoding		Encoding (7bit,8bit,base64,quoted-printable,binary)
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if encoding is invalid
	 */
	protected static function encodeContent( $content, $encoding, $split = TRUE ): string
	{
		$delimiter	= Message::$delimiter;
		$lineLength	= Message::$lineLength;
		switch( strtolower( $encoding ) ){
			case '7bit':
			case '8bit':
				$content	= mb_convert_encoding( $content, 'UTF-8', strtolower( $encoding ) );
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
					$content	= imap_8bit( $content );
				else
					$content	= quoted_printable_encode( $content );
				break;
			case '':
				break;
			default:
				throw new \InvalidArgumentException( 'Encoding method "'.$encoding.'" is not supported' );
		}
		return $content;
	}

	protected function getMimeTypeFromFile( $fileName ): string
	{
		if( !file_exists( $fileName ) )
			throw new \InvalidArgumentException( 'File "'.$fileName.'" is not existing' );
		$finfo	= finfo_open( FILEINFO_MIME_TYPE );
		$type	= finfo_file( $finfo, $fileName );
		finfo_close( $finfo );
		return $type;
	}

	protected static function wrapContent( $content, $length = NULL, $delimiter = NULL ): string
	{
		$delimiter	= $delimiter ? $delimiter : Message::$delimiter;
		$lineLength	= $length ? $length : Message::$lineLength;
		$content	= chunk_split( $content, $lineLength, $delimiter );
		return rtrim( $content, $delimiter );
	}
}
