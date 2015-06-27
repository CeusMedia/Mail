<?php
/**
 *	Mail Attachment Data Object.
 *
 *	Copyright (c) 2007-2015 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Part;
/**
 *	Mail Attachment Data Object.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Text extends \CeusMedia\Mail\Part{

	public function __construct( $content, $charset = 'UTF-8', $encoding = 'quoted-printable' ){
		$this->setContent( $content );
		$this->setMimeType( 'text/plain' );
		$this->setCharset( $charset );
		$this->setFormat( 'fixed' );
		$this->setEncoding( $encoding );
	}

	public function render(){
		switch( strtolower( $this->encoding ) ){
			case '7bit':
			case '8bit':
				$content	= mb_convert_encoding( $this->content, "UTF-8", strtolower( $this->encoding ) );
				break;
			case 'base64':
			case 'binary':
				$content	= base64_encode( $this->content );
				$content	= chunk_split( $content, 76 );
				break;
			case 'quoted-printable':
				$content	= quoted_printable_encode( $this->content );
				break;
			default:
				$content	= $this->content;
		}
		$headers		= new \CeusMedia\Mail\Header\Section();
		$contentType	= array(
			$this->mimeType,
			'charset="'.trim( $this->charset ).'"',
//			'format="'.$this->format.'"'
		);
		$headers->addFieldPair( 'Content-Type', join( ";\r\n ", $contentType ) );
		$headers->addFieldPair( 'Content-Transfer-Encoding', $this->encoding );
		return $headers->toString()."\r\n"."\r\n".$content;
	}
}
