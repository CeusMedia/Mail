<?php
declare(strict_types=1);

/**
 *	...
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Section;

use DomainException;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@todo			implement
 *	@todo			code doc
 */
class Renderer
{
	/**
	 *	@var		array		$encodeOptionKeys		List of available encoding option keys
	 *	@static
	 */
	protected static $encodeOptionKeys	= [
		'scheme',
		'input-charset',
		'output-charset',
		'line-length',
		'line-break-chars',
	];

	/**
	 *	@var		array		$preferences			Map of encoding options
	 *	@static
	 */
	protected static $preferences = [
	];

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 *	Render complete header section.
	 *	@access		public
	 *	@static
	 *	@param		Section			$section		...
	 *	@param		boolean			$keepCase		...
	 *	@return		string
	 */
	public static function render( Section $section, bool $keepCase = FALSE ): string
	{
		$lines	= array_map(static function( $field ) use ( $keepCase ) {
			return static::renderField( $field, $keepCase );
		}, $section->getFields() );
		return implode( Message::$delimiter, $lines );
	}

	/**
	 *	Render header field.
	 *	@access		public
	 *	@static
	 *	@param		Field			$field			...
	 *	@param		boolean			$keepCase		...
	 *	@return		string
	 *	@todo 		investigate further, how iconv_mime_encode could help encoding header values
	 */
	public static function renderField( Field $field, bool $keepCase = FALSE ): string
	{
		$attr	= '';
		foreach( $field->getAttributes() as $key => $content )
			$attr	.= sprintf( '; %s="%s"', $key, addslashes( $content ) );

		return $field->getName( $keepCase ).': '.$field->getValue().$attr;
//		if( preg_match( "/^[\w\s\.-:#]+$/", $field->getValue() ) )
//			return $field->getName( $keepCase ).": ".$field->getValue();
//		return iconv_mime_encode( $field->getName(), $field->getValue(), self::$preferences );
	}

	public static function renderAttributedValue( AttributedValue $value ): string
	{
		$attr	= '';
		foreach( $value->getAttributes() as $key => $content )
			$attr	.= sprintf( '; %s="%s"', $key, addslashes( $content ) );
		return $value->getValue().$attr;
	}

	/**
	 *	Set encoding options.
	 *	@access		public
	 *	@static
	 *	@param		string		$key		Option key
	 *	@param		string		$value		Option valie
	 *	@return		void
	 *	@see		https://www.php.net/manual/en/function.iconv-mime-encode.php
	 */
	public static function setEncodeOption( string $key, $value )
	{
		if( !in_array( $key, static::$encodeOptionKeys, TRUE ) )
			throw new DomainException( 'Invalid encoding option key: '.$key );
		self::$preferences[$key]	= $value;
	}
}
