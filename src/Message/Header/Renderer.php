<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2007-2025 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Message;

use InvalidArgumentException;

use function addcslashes;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strlen;
use function strpbrk;
use function strpos;

/**
 *	Renderer for mail header sections and fields, rejects header injection.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
class Renderer
{
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
	 *	@throws		InvalidArgumentException	if name is invalid or value contains line breaks, which are not folding
	 */
	public static function renderField( Field $field, bool $keepCase = FALSE ): string
	{
		$name	= $field->getName( $keepCase );
		if( 1 !== preg_match( '/^'.Encoding::PATTERN_FIELD_NAME.'$/', $name ) )			//  printable ASCII without colon (RFC 5322, 2.2)
			throw new InvalidArgumentException( sprintf( 'Invalid header field name "%s"', addcslashes( $name, "\0..\37" ) ) );
		$value	= static::appendParameters( $field->getValue(), $field->getAttributes(), strlen( $name.': ' ) );
		static::checkLineBreaks( $name, $value );
		return $name.': '.$value;
	}

	public static function renderAttributedValue( AttributedValue $value ): string
	{
		return static::appendParameters( $value->getValue(), $value->getAttributes() );
	}

	/**
	 *	Set encoding options. Without effect, header values are encoded by Encoding.
	 *	@access		public
	 *	@static
	 *	@param		string		$key		Option key
	 *	@param		string		$value		Option value
	 *	@return		void
	 *	@deprecated	without effect, use Encoding to encode header values
	 *	@todo		to be removed
	 *	@codeCoverageIgnore
	 */
	public static function setEncodeOption( string $key, string $value ): void
	{
		Deprecation::getInstance()
			->setErrorVersion( '2.8' )
			->setExceptionVersion( '2.9' )
			->message( 'Method setEncodeOption is without effect, use Encoding to encode header values' );
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Appends parameters to header value, folded before a parameter not fitting in line anymore.
	 *	@access		protected
	 *	@static
	 *	@param		string					$value			Header value
	 *	@param		array<string|int,string>	$parameters		Map of parameters
	 *	@param		integer					$offset			Length of header name, colon and space
	 *	@return		string
	 */
	protected static function appendParameters( string $value, array $parameters, int $offset = 0 ): string
	{
		$length	= Encoding::getLastLineLength( $value, $offset );
		foreach( $parameters as $key => $content ){
			$parameter	= Encoding::renderParameter( (string) $key, $content );
			$position	= strpos( $parameter, Message::$delimiter );
			$firstLine	= FALSE === $position ? strlen( $parameter ) : $position;
			if( $length + 2 + $firstLine > Encoding::LINE_LENGTH ){
				$value	.= ';'.Message::$delimiter.' '.$parameter;
				$length	= Encoding::getLastLineLength( $parameter, 1 );
			}
			else{
				$value	.= '; '.$parameter;
				$length	= Encoding::getLastLineLength( $parameter, $length + 2 );
			}
		}
		return $value;
	}

	/**
	 *	Prevents header injection: line breaks are allowed only for folding,
	 *	i.e. a line delimiter followed by space or tab (RFC 5322, 2.2.3).
	 *	@access		protected
	 *	@static
	 *	@param		string		$name		Header field name, for exception message
	 *	@param		string		$value		Rendered header field value
	 *	@return		void
	 *	@throws		InvalidArgumentException	if value contains a line break, which is not folding
	 */
	protected static function checkLineBreaks( string $name, string $value ): void
	{
		$unfolded	= (string) preg_replace( '/'.preg_quote( Message::$delimiter, '/' ).'[ \t]/', ' ', $value );
		if( FALSE !== strpbrk( $unfolded, "\r\n" ) )
			throw new InvalidArgumentException( sprintf( 'Header field "%s" contains line break', $name ) );
	}
}
