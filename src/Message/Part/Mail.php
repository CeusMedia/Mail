<?php
/**
 *	Mail Attachment Data Object.
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Part as MessagePart;
use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

/**
 *	Mail Attachment Data Object.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Mail extends MessagePart
{
	public function __construct( $content, $charset = 'UTF-8', $encoding = 'quoted-printable' )
	{
		$this->setContent( $content );
		$this->setMimeType( 'text/plain' );
		$this->setCharset( $charset );
		$this->setFormat( 'fixed' );
		$this->setEncoding( $encoding );
	}

	/**
	 *	Returns string representation of mail part for rendering whole mail.
	 *	@access		public
	 *	@param		boolean		$headers		Flag: render part with headers
	 *	@return		string
	 */
	public function render( $headers = NULL ): string
	{
		$delim	= Message::$delimiter;
		if( !$headers )
			$headers	= new MessageHeaderSection();
		$headers->setFieldPair( 'Content-Type', join( '; ', array(
			$this->mimeType,
			'charset="'.strtolower( trim( $this->charset ) ).'"',
			'format='.$this->format
		) ) );
		$headers->setFieldPair( 'Content-Transfer-Encoding', $this->encoding );
		$content	= static::encodeContent( $this->content, $this->encoding );
		return $headers->toString( TRUE ).$delim.$delim.$content;
	}
}
