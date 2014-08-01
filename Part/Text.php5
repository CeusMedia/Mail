<?php
/**
 *	Mail Attachment Data Object.
 *
 *	Copyright (c) 2010-2014 Christian Würker (ceusmedia.de)
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
 *	@category		cmModules
 *	@package		Mail.Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id: Attachment.php5 1080 2013-07-23 01:56:47Z christian.wuerker $
 */
/**
 *	Mail Attachment Data Object.
 *
 *	@category		cmModules
 *	@package		Mail.Part
 *	@extends		CMM_Mail_Part_Abstract
 *	@uses			CMM_Mail_Header_Section
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@version		$Id: Attachment.php5 1080 2013-07-23 01:56:47Z christian.wuerker $
 */
class CMM_Mail_Part_Text extends CMM_Mail_Part_Abstract{

	public function __construct( $content, $charset = 'UTF-8', $encoding = 'quoted-printable' ){
		$this->setContent( $content );
		$this->setMimeType( 'text/plain' );
		$this->setCharset( $charset );
		$this->setFormat( 'fixed' );
		$this->setEncoding( $encoding );
	}

	public function render(){
		switch( strtolower( $this->encoding ) ){
			case 'base64':
				$content	= base64_encode( $this->content );
				break;
			case 'quoted-printable':
				$content	= quoted_printable_encode( $this->content );
				break;
			default:
				$content	= $this->content;
		}
		$content		= chunk_split( $content, 76 );
		$headers		= new CMM_Mail_Header_Section();
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
