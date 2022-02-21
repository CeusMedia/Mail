<?php
declare(strict_types=1);

/**
 *	Mail Attachment Data Object.
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
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

use function array_reverse;
use function join;

/**
 *	Mail Attachment Data Object.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
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
	 *	@param		integer						$sections				Section(s) to render, default: all
	 *	@param		MessageHeaderSection|NULL	$additionalHeaders		Section with header fields to render aswell
	 *	@return		string
	 */
	public function render( int $sections = self::SECTION_ALL, ?MessageHeaderSection $additionalHeaders = NULL ): string
	{
		$doAll		= self::SECTION_ALL === ( $sections & self::SECTION_ALL );
		$doHeader	= self::SECTION_HEADER === ( $sections & self::SECTION_HEADER );
		$doContent	= self::SECTION_CONTENT === ( $sections & self::SECTION_CONTENT );
		$delim		= Message::$delimiter;
		$list		= [];

		$section	= $additionalHeaders ?? new MessageHeaderSection();

		if( $doContent || $doAll ){
			$content	= static::encodeContent( $this->content ?? '', $this->encoding );
			$list[]		= $content;
//			$section->setFieldPair( 'Content-Length', (string) $content );
		}

		if( $doHeader || $doAll ){
			$section->setFieldPair( 'Content-Type', join( '; ', [
				$this->mimeType,
				'charset="'.strtolower( trim( $this->charset ) ).'"',
				'format='.$this->format
			] ) );
			$section->setFieldPair( 'Content-Transfer-Encoding', $this->encoding );
			$list[]		= $section->toString( TRUE );
		}

		return join( $delim.$delim, array_reverse( $list ) );
	}
}
