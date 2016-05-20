<?php
/**
 *	Abstract Mail Part.
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
 *	Abstract Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
abstract class Part{

	protected $charset;
	protected $content;
	protected $encoding;
	protected $format;
	protected $mimeType;

	protected function encode( $content, $encoding, $split = TRUE ){
		$delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$lineLength	= \CeusMedia\Mail\Message::$lineLength;
		switch( strtolower( $encoding ) ){
			case '7bit':
			case '8bit':
				$content	= mb_convert_encoding( $content, "UTF-8", strtolower( $encoding ) );
				if( $split )
					$content	= wordwrap( $content, $lineLength, $delimiter, TRUE );
				break;
			case 'base64':
			case 'binary':
				$content	= base64_encode( $content );
				if( $split )
					$content	= chunk_split( $content, $lineLength, $delimiter );
				break;
			case 'quoted-printable':
				$content	= quoted_printable_encode( $content );
				break;
			default:
				throw new \InvalidArgumentException( 'Encoding method "'.$encoding.'" is not supported' );
		}
		return $content;
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

	abstract public function render();

	public function setCharset( $charset ){
		$this->charset	= $charset;
	}

	public function setContent( $content ){
		$this->content	= $content;
	}

	public function setEncoding( $encoding ){
		$encodings	= array( "7bit", "8bit", "base64", "quoted-printable", "binary" );
		if( !in_array( $encoding, $encodings ) )
			throw new \InvalidArgumentException( 'Invalid encoding' );
		$this->encoding	= $encoding;
	}

	public function setFormat( $format ){
		$formats	= array( "fixed", "flowed" );
		if( !in_array( $format, $formats ) )
			throw new \InvalidArgumentException( 'Invalid format' );
		$this->format	= $format;
	}

	public function setMimeType( $mimeType ){
		$this->mimeType	= $mimeType;
	}
}
?>
