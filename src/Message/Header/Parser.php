<?php
declare(strict_types=1);

/**
 *	Parser for mail headers.
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
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Mail\Conduct\RegularStringHandling;

use RangeException;
use RuntimeException;

use function array_shift;
use function array_walk;
use function count;
use function explode;
use function iconv;
use function iconv_mime_decode_headers;
use function is_array;
use function is_null;
use function ltrim;
use function mb_strlen;
use function strtoupper;
use function substr;
use function trim;
use function urldecode;

/**
 *	Parser for mail headers.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			implement IMAP parser
 */
class Parser
{
	use RegularStringHandling;

	public const STRATEGY_AUTO				= 0;
	public const STRATEGY_OWN				= 3;	//  own implementation, supports DKIM key folding and RFC 2231
	public const STRATEGY_ICONV				= 4;	//  iconv, not supporting DKIM key folding and RFC 2231
	public const STRATEGY_ICONV_STRICT		= 5;	//  iconv in strict mode, not supporting DKIM key folding and RFC 2231
	public const STRATEGY_ICONV_TOLERANT	= 6;	//  iconv in tolerant mode, not supporting DKIM key folding and RFC 2231

	public const STRATEGIES		= [
		self::STRATEGY_AUTO,
		self::STRATEGY_OWN,
		self::STRATEGY_ICONV,
		self::STRATEGY_ICONV_STRICT,
		self::STRATEGY_ICONV_TOLERANT,
	];

	/** @var		integer		$defaultStrategy			Strategy to use in auto mode */
	protected int $defaultStrategy	= self::STRATEGY_OWN;

	/** @var		integer		$strategy				Strategy to use */
	protected int $strategy			= self::STRATEGY_AUTO;

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@param		integer		$strategy		Optional: strategy to set, leaving auto mode
	 *	@return		self
	 */
	public static function getInstance( int $strategy = self::STRATEGY_AUTO ): self
	{
		$instance	= new self;
		$instance->setStrategy( $strategy );
		return $instance;
	}

	public function parse( string $content ): Section
	{
		$strategy	= $this->strategy;
		if( $this->strategy === self::STRATEGY_AUTO )
			$strategy	= $this->defaultStrategy;

		switch( $strategy ){
			case self::STRATEGY_OWN:
				return self::parseByOwnStrategy( $content );
			case self::STRATEGY_ICONV:
				return self::parseByIconvStrategy( $content, 0 );
			case self::STRATEGY_ICONV_STRICT:
				return self::parseByIconvStrategy( $content, 1 );
			case self::STRATEGY_ICONV_TOLERANT:
				return self::parseByIconvStrategy( $content, 2 );
			default:
				throw new RuntimeException( 'Unsupported strategy' );
		}
	}

	/**
	 *	Splits up header values with attributes, like: text/csv; filename="test.csv"
	 *	Applies RFC 2231 to support attribute encoding, (language) and containments (=attribute value folding).
	 *	Return a map object with pure header value and attributes dictionary.
	 *	@access		public
	 *	@static
	 *	@param		string			$headerValue		Complete header field value, may be multiline
	 *	@return 	AttributedValue	Map object with pure header value and attributes dictionary
	 *	@throws		RuntimeException					if parsing the value failed
	 */
	public static function parseAttributedHeaderValue( string $headerValue ): AttributedValue
	{
		$string	= self::regReplace( "/\r?\n/", "", $headerValue, 'Unfolding the value failed' );
		$parts	= self::regSplit( '/\s*;\s*/', trim( $string ), 0, 'Parsing the value failed' );
		$value	= array_shift( $parts );
		$list	= [];
		if( 0 !== count( $parts ) ){
			foreach( $parts as $part ){
				$hasAssignment	= self::regMatch( '/=/', $part, 'Parsing the value failed' );
				if( !$hasAssignment )
					continue;
				$p = self::regSplit( '/\s?=\s?/', $part, 2, 'Parsing the value failed' );
				if( trim( $p[1][0] ) === '"' )
					$p[1]	= substr( trim( $p[1] ), 1, -1 );
				$hasLabel	= self::regMatch( '/\*\d+\*?$/', $p[0], 'Parsing the value failed' );
				$valueLine	= stripslashes( $p[1] );
				if( $hasLabel ){
					$label	= self::regReplace( '/^(.+)\*\d+\*?$/', '\\1', $p[0] );
					if( !isset( $list[$label] ) )
						$list[$label]	= '';
					$list[$label]	.= $valueLine;
				}
				else
					$list[$p[0]]	= $valueLine;
			}
		}

		//  Apply RFC 2231 (https://datatracker.ietf.org/doc/html/rfc2231)
		$rfc2231	= "/^(?<charset>[A-Z0-9\-]+)(\'(?<language>[A-Z\-]{0,5})\')(?<content>.*)$/i";	//  eG. utf-8'en'Some%20content
		array_walk( $list, function( &$value, $key ) use ( $rfc2231 ): void {						//  apply RFC expr to list
			if( self::regMatch( $rfc2231, $value, NULL, $matches ) ){								//  encoding prefix found
				$m	= (object) $matches;															//  shortcut matches
				if( strtoupper( $m->charset ) !== 'UTF-8' )											//  encoding differs from UTF-8
					$m->content    = iconv( $m->charset, 'UTF-8', $m->content );					//  recode content to UTF-8
				$value = urldecode( $m->content );													//  remove prefix and decode content
			}
		} );

		return new AttributedValue( $value, $list );
	}

	/**
	 *	...
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Header fields block to parse
	 *	@param		integer		$mode			iconv mode (0-normal, 1-strict, 2-tolerant), default: 0
	 *	@return		Section
	 *	@throws		RuntimeException			if decoding of header failed
 	 */
	public static function parseByIconvStrategy( string $content, int $mode = 0 ): Section
	{
		$headers	= iconv_mime_decode_headers( $content, $mode, 'UTF-8' );
		if( FALSE === $headers )
			throw new RuntimeException( 'Decoding of header failed' );

		$section	= new Section();
		foreach( $headers as $key => $values ){
			if( !is_array( $values ) )
				$values	= [$values];
			foreach( $values as $value ){
				$field	= new Field( $key, $value );
				$attributedValue = self::parseAttributedHeaderValue( $value );
				if( $attributedValue->getValue() !== $value ){
					$field->setValue( $attributedValue->getValue() );
					$field->setAttributes( $attributedValue->getAttributes() );
				}
				$section->addField( $field );
			}
		}
		return $section;
	}

	public static function parseByOwnStrategy( string $content ): Section
	{
		$encoder	= Encoding::getInstance();

		$lines		= self::regSplit( "/\r?\n/", $content, 0, 'Splitting of header failed' );
		$fws		= '';
		$buffer		= [];
		$field		= NULL;
		$section	= new Section();
		foreach( $lines as $nr => $line ){
			if( self::regMatch( '/^\S+:/', $line ) ){
				[$key, $value] = explode( ':', $line, 2 );
				$value	= $encoder->decodeByOwnStrategy( ltrim( $value ) );
				$field	= new Field( $key, $value );
				$section->addField( $field );
				$buffer	= [$value];
				$fws	= '';
			}
			else if( NULL !== $field ){											//  line is folded line
				if( 0 === mb_strlen( $fws ) )									//  folding white space not detected yet
					$fws	= self::regReplace( '/^(\s+).+$/', '\\1', $line,	//  get only folding white space
						'Unfolding the value failed' );
				$reducedLine	= substr( $line, strlen( $fws ) );				//  reduce line by folding white space
				if( self::regMatch( '/^\s/', $reducedLine ) )					//  reduced line still contains leading white space
					$buffer[]	= ltrim( $line );								//  folding @ level 2: folded structure header field
				else{															//  reduced line is folding @ level 1
					$line		= ' '.ltrim( $line );							//  reduce leading white space to one
					$buffer[]	= $encoder->decodeByOwnStrategy( $line );		//  collect decoded line
				}
				$field->setValue( join( $buffer ) );							//  set unfolded field value
			}
		}

		//  iterate all fields again to detect attributed values
		$finalSection	= new Section();
		foreach( $section->getFields() as $foundField ){
			$attributedValue = self::parseAttributedHeaderValue( $foundField->getValue() );
			if( $attributedValue->hasAttributes() ){
				$foundField->setValue( $attributedValue->getValue() );				//  set unfolded field value
				$foundField->setAttributes( $attributedValue->getAttributes() );
			}
			$finalSection->addField( $foundField );
		}
		return $finalSection;
	}

	public function setStrategy( int $strategy ): self
	{
		if( !in_array( $strategy, self::STRATEGIES, TRUE ) )
			throw new RangeException( 'Invalid strategy' );
		$this->strategy	= $strategy;
		return $this;
	}
}
