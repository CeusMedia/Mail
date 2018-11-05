<?php
/**
 *	Abstract Mail Part.
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
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Part as MessagePart;
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
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
abstract class Part{

	const TYPE_UNKNOWN			= 0;
	const TYPE_TEXT				= 1;
	const TYPE_MAIL				= 2;
	const TYPE_ATTACHMENT		= 3;
	const TYPE_HTML				= 4;
	const TYPE_INLINE_IMAGE		= 5;

	static $typeMap				= array(
		'TEXT'				=> 'Text',
		'MAIL'				=> 'Mail',
		'ATTACHMENT'		=> 'Attachment',
		'HTML'				=> 'HTML',
		'INLINE_IMAGE'		=> 'InlineImage',
	);

	protected $charset;
	protected $content;
	protected $encoding;
	protected $format;
	protected $mimeType;

	static public function decodeContent( $content, $encoding ){
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

	static public function encodeContent( $content, $encoding, $split = TRUE ){
		$delimiter	= Message::$delimiter;
		$lineLength	= Message::$lineLength;
		switch( strtolower( $encoding ) ){
			case '7bit':
			case '8bit':
				$content	= mb_convert_encoding( $content, 'UTF-8', strtolower( $encoding ) );
				if( $split && strlen( $content ) > $lineLength )
					$content	= static::wrapContent( $content, $lineLength, $delimiter, TRUE );
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

	static function wrapContent( $content, $length = NULL, $delimiter = NULL ){
		$delimiter	= $delimiter ? $delimiter : Message::$delimiter;
		$lineLength	= $length ? $length : Message::$lineLength;
		$content	= chunk_split( $content, $lineLength, $delimiter );
		return rtrim( $content, $delimiter );
	}

	public function getCharset(){
		return $this->charset;
	}

	public function getContent(){
		return $this->content;
	}

	public function getEncoding(){
		return $this->encoding;
	}

	public function getFormat(){
		return $this->format;
	}

	public function getMimeType(){
		return $this->mimeType;
	}

	protected function getMimeTypeFromFile( $fileName ){
		if( !file_exists( $fileName ) )
			throw new \InvalidArgumentException( 'File "'.$fileName.'" is not existing' );
		$finfo	= finfo_open( FILEINFO_MIME_TYPE );
		$type	= finfo_file( $finfo, $fileName );
		finfo_close( $finfo );
		return $type;
	}

	public function getType(){
		if( $this instanceof Text )
			return static::TYPE_TEXT;
		if( $this instanceof MessagePartMail )
			return static::TYPE_MAIL;
		if( $this instanceof MessagePartAttachment )
			return static::TYPE_ATTACHMENT;
		if( $this instanceof MessagePartHTML )
			return static::TYPE_HTML;
		if( $this instanceof MessagePartInlineImage )
			return static::TYPE_INLINE_IMAGE;
		return static::TYPE_UNKNOWN;
	}

	public function isAttachment(){
		return $this->getType() === static::TYPE_ATTACHMENT;
	}

	public function isHTML(){
		return $this->getType() === static::TYPE_HTML;
	}

	public function isInlineImage(){
		return $this->getType() === static::TYPE_INLINE_IMAGE;
	}

	public function isMail(){
		return $this->getType() === static::TYPE_MAIL;
	}

	public function isOfType( $type ){
		return $this->getType() === $type;
	}

	public function isText(){
		return $this->getType() === static::TYPE_TEXT;
	}

	abstract public function render();

	public function setCharset( $charset ){
		$this->charset	= $charset;
	}

	public function setContent( $content ){
		$this->content	= $content;
	}

	public function setEncoding( $encoding ){
		$encodings	= array( '', '7bit', '8bit', 'base64', 'quoted-printable', 'binary' );
		if( !in_array( $encoding, $encodings ) )
			throw new \InvalidArgumentException( 'Invalid encoding: '.$encoding );
		$this->encoding	= $encoding;
	}

	public function setFormat( $format ){
		$formats	= array( 'fixed', 'flowed' );
		if( !in_array( $format, $formats ) )
			throw new \InvalidArgumentException( 'Invalid format' );
		$this->format	= $format;
	}

	public function setMimeType( $mimeType ){
		$this->mimeType	= $mimeType;
	}
}
?>
