<?php
declare(strict_types=1);

/**
 *	Parser for mail headers.
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
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Mail\Util\Str;

use RuntimeException;

use function count;
use function ksort;
use function in_array;
use function ltrim;
use function strtolower;
use function substr;
use function rawurldecode;
use function str_contains;
use function str_replace;
use function strlen;
use function trim;

/**
 *	Parser for mail headers.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			implement IMAP parser
 */
class Parser
{
	/** Header fields with parameters, like: text/csv; name="test.csv" */
	public const PARAMETER_FIELDS		= [
		'content-type',
		'content-disposition',
	];

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function getInstance(): self
	{
		return new self;
	}

	/**
	 *	Parses header fields block. Values are decoded by Encoding (RFC 2047), parameters split (RFC 2231).
	 *	@access		public
	 *	@param		string		$content		Header fields block to parse
	 *	@return		Section
	 *	@throws		RuntimeException			if splitting of header fails
	 */
	public function parse( string $content ): Section
	{
		$encoder	= Encoding::getInstance();
		$section	= new Section();
		foreach( self::splitFields( $content ) as [$name, $value] )
			$section->addField( self::createField( $name, $value, $encoder ) );
		return $section;
	}

	/**
	 *	Splits up header values with attributes, like: text/csv; filename="test.csv"
	 *	Applies RFC 2231 to support attribute encoding, (language) and containment (=attribute value folding).
	 *	Return a map object with pure header value and attributes dictionary.
	 *	@access		public
	 *	@static
	 *	@param		string			$headerValue		Complete header field value, may be multiline
	 *	@return 	AttributedValue	Map object with pure header value and attributes dictionary
	 *	@throws		RuntimeException					if parsing the value failed
	 */
	public static function parseAttributedHeaderValue( string $headerValue ): AttributedValue
	{
		$string		= trim( Encoding::unfold( $headerValue ) );
		$quoted		= Encoding::PATTERN_QUOTED;
		Str::regMatch( '/^(?:'.$quoted.'|[^;"]++)*+/', $string, $matches );			//  value up to first semicolon outside quotes, possessive: no stack limit
		$value		= trim( $matches[0] ?? '' );
		$rest		= substr( $string, strlen( $matches[0] ?? '' ) );
		$parameters	= Str::regMatchAll( '/;\s*([^\s=;]+)\s*=\s*('.$quoted.'|[^;]*)/', $rest );

		$list		= [];
		$extended	= [];
		foreach( $parameters as $parameter ){
			$key		= strtolower( $parameter[1] );									//  parameter names are case-insensitive (RFC 2045, 5.1)
			$content	= trim( $parameter[2] );
			$content	= Encoding::unquote( $content );
			//  RFC 2231: name* (encoded), name*0, name*1 (continuations), name*0*, name*1* (encoded continuations)
			if( Str::regMatch( '/^(.+?)\*(\d+)?(\*)?$/', $key, $parts ) ){
				$encoded	= !isset( $parts[2] ) || '' === $parts[2] || isset( $parts[3] );
				$extended[$parts[1]][(int) ( $parts[2] ?? 0 )]	= [$content, $encoded];
				$list[$parts[1]]	??= '';												//  keep order of attributes
				continue;
			}
			$list[$key]	= $content;
		}

		foreach( $extended as $label => $segments ){										//  extended value wins over plain value
			ksort( $segments );
			$charset	= 'UTF-8';
			$result		= '';
			foreach( $segments as $index => [$segment, $encoded] ){
				if( $encoded ){
					if( 0 === $index && Str::regMatch( "/^([^']*)'[^']*'(.*)$/s", $segment, $parts ) ){
						$charset	= '' !== $parts[1] ? $parts[1] : $charset;					//  charset'language'content
						$segment	= $parts[2];
					}
					$segment	= rawurldecode( $segment );
				}
				$result	.= $segment;
			}
			$result			= Encoding::getInstance()->convertToCharset( $result, $charset );
			$list[$label]	= $result;
		}
		return new AttributedValue( $value, $list );
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Creates header field from raw, unfolded value.
	 *	Address lists are decoded name by name, so names with special characters, like "Doe, Jürgen", are quoted
	 *	and the list can still be split. Parameters (Content-Type, Content-Disposition) are split before decoding,
	 *	so decoded quotes or semicolons do not break them. Other values are decoded as a whole.
	 *	@access		protected
	 *	@static
	 *	@param		string		$name			Header field name
	 *	@param		string		$value			Raw header field value, unfolded
	 *	@param		Encoding	$encoder		Encoder to decode with
	 *	@return		Field
	 */
	protected static function createField( string $name, string $value, Encoding $encoder ): Field
	{
		$key	= strtolower( $name );
		if( in_array( $key, Encoding::ADDRESS_FIELDS, TRUE ) )
			return new Field( $name, $encoder->decodeAddressListIfNeeded( $value ) );
		if( !in_array( $key, self::PARAMETER_FIELDS, TRUE ) )
			return new Field( $name, $encoder->decodeIfNeeded( $value ) );

		$attributedValue	= self::parseAttributedHeaderValue( $value );
		$attributes			= [];
		foreach( $attributedValue->getAttributes() as $attributeKey => $attributeValue )		//  encoded words in quoted parameter: not allowed, but common
			$attributes[$attributeKey]	= str_contains( $attributeValue, '=?' ) ? $encoder->decodeIfNeeded( $attributeValue ) : $attributeValue;
		return new Field( $name, $encoder->decodeIfNeeded( $attributedValue->getValue() ), $attributes );
	}

	/**
	 *	Splits header fields block into list of field names and raw, unfolded values.
	 *	Values are decoded later, once per field: white space between encoded words spanning lines is dropped (RFC 2047, 6.2).
	 *	@access		protected
	 *	@static
	 *	@param		string		$content		Header fields block
	 *	@return		array<array{string,string}>
	 *	@throws		RuntimeException			if splitting fails
	 */
	protected static function splitFields( string $content ): array
	{
		$names		= [];
		$values		= [];
		$skip		= FALSE;
		foreach( Str::regSplit( "/\r?\n/", $content, 0, 'Splitting of header failed' ) as $line ){
			$line	= str_replace( "\r", ' ', $line );									//  bare CR (not before LF): no line break, could not be rendered again
			if( Str::regMatch( '/^([^\s:]+)[ \t]*:(.*)$/s', $line, $matches ) ){
				//  skip field with invalid name (printable ASCII without colon, RFC 5322, 2.2), it could not be rendered again
				$skip	= !Str::regMatch( '/^'.Encoding::PATTERN_FIELD_NAME.'$/', $matches[1] );
				if( !$skip ){
					$names[]	= $matches[1];
					$values[]	= ltrim( $matches[2] );
				}
				continue;
			}
			$last	= count( $values ) - 1;
			if( $skip || $last < 0 || !Str::hasContent( $line ) )
				continue;
			$values[$last]	.= ' '.ltrim( $line );									//  folded line, leading white space reduced to one space
		}
		$fields	= [];
		foreach( $names as $nr => $name )
			$fields[]	= [$name, $values[$nr]];
		return $fields;
	}
}
