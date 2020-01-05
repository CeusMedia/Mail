<?php
/**
 *	Mail Attachment Data Object.
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
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Text extends MessagePart
{
	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$content		Text content
	 *	@param		string		$charset		Character set to set, default: UTF-8
	 *	@param		string		$encoding		Encoding to set, default: base64, values: 7bit,8bit,base64,quoted-printable,binary
	 */
	public function __construct( string $content, string $charset = 'UTF-8', string $encoding = 'base64' )
	{
		$this->type		= static::TYPE_TEXT;
		$this->setContent( $content );
		$this->setMimeType( 'text/plain' );
		$this->setCharset( $charset );
		$this->setEncoding( $encoding );
		$this->setFormat( 'fixed' );
	}

	/**
	 *	Returns string representation of mail part for rendering whole mail.
	 *	@access		public
	 *	@param		boolean		$headers		Flag: render part with headers
	 *	@return		string
	 */
	public function render( $headers = NULL ): string
	{
		if( !$headers )
			$headers	= new MessageHeaderSection();
		$delim	= Message::$delimiter;
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
