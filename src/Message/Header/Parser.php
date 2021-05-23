<?php
declare(strict_types=1);

/**
 *	Parser for mail headers.
 *
 *	Copyright (c) 2007-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

/**
 *	Parser for mail headers.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@todo			implement IMAP parser
 */
class Parser
{
	const STRATEGY_AUTO				= 0;
	const STRATEGY_FIRST			= 1;						//  first implementation, not supporting DKIM key folding and RFC 2231
	const STRATEGY_SECOND			= 2;						//  second implementation, not supporting DKIM key folding and RFC 2231
	const STRATEGY_THIRD			= 3;						//  own implementation, supports DKIM key folding and RFC 2231
	const STRATEGY_ICONV			= 4;						//  iconv, not supporting DKIM key folding and RFC 2231
	const STRATEGY_ICONV_STRICT		= 5;						//  iconv in strict mode, not supporting DKIM key folding and RFC 2231
	const STRATEGY_ICONV_TOLERANT	= 6;						//  iconv in tolerant mode, not supporting DKIM key folding and RFC 2231

	const STRATEGIES			= [
		self::STRATEGY_AUTO,
		self::STRATEGY_FIRST,
		self::STRATEGY_SECOND,
		self::STRATEGY_THIRD,
		self::STRATEGY_ICONV,
		self::STRATEGY_ICONV_STRICT,
		self::STRATEGY_ICONV_TOLERANT,
	];

	/** @var		integer		$defaultStategy			Strategy to use in auto mode */
//	protected $defaultStategy	= self::STRATEGY_ICONV_TOLERANT;
	protected $defaultStategy	= self::STRATEGY_THIRD;

	/** @var		integer		$strategy				Strategy to use */
	protected $strategy			= self::STRATEGY_AUTO;

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@param		integer		$strategy		Optional: strategy to set, leaving auto mode
	 *	@return		self
	 */
	public static function getInstance( int $strategy = NULL ): self
	{
		$instance	= new self;
		if( !is_null( $strategy ) )
			$instance->setStrategy( $strategy );
		return $instance;
	}

	public function parse( string $content ): Section
	{
		$strategy	= $this->strategy;
		if( $this->strategy === self::STRATEGY_AUTO )
			$strategy	= $this->defaultStategy;

		switch( $strategy ){
			case self::STRATEGY_FIRST:
				return self::parseByFirstStrategy( $content );
			case self::STRATEGY_SECOND:
				return self::parseBySecondStrategy( $content );
			case self::STRATEGY_THIRD:
				return self::parseByThirdStrategy( $content );
			case self::STRATEGY_ICONV:
				return self::parseByIconvStrategy( $content, 0 );
			case self::STRATEGY_ICONV_STRICT:
				return self::parseByIconvStrategy( $content, 1 );
			case self::STRATEGY_ICONV_TOLERANT:
				return self::parseByIconvStrategy( $content, 2 );
			default:
				throw new \RuntimeException( 'Unsupported strategy' );
		}
	}

	/**
	 *	Splits up header field value with attributes, like: text/csv; filename="test.csv"
	 *	Applies RFC 2231 to support attribute encoding, (language) and containments (=attribute value folding).
	 *	Returns instance of attributed header field.
	 *	@access		public
	 *	@static
	 *	@param		Field	$field		Instance of header field
	 *	@return 	AttributedField
	 */
	public static function parseAttributedField( Field $field ): AttributedField
	{
		$object	= self::parseAttributedHeaderValue( $field->getValue() );
		$field	= new AttributedField( $field->getName(), $object->value );
		$field->setAttributes( $object->attributes->getAll() );
		return $field;
	}

	/**
	 *	Splits up header values with attributes, like: text/csv; filename="test.csv"
	 *	Applies RFC 2231 to support attribute encoding, (language) and containments (=attribute value folding).
	 *	Return a map object with pure header value and attributes dictionary.
	 *	@access		public
	 *	@static
	 *	@param		string		$headerValue		Complete header field value, may be multiline
	 *	@return 	object		Map object with pure header value and attributes dictionary
	 */
	public static function parseAttributedHeaderValue( string $headerValue )
	{
		$string	= trim( preg_replace( "/\r?\n/", "", $headerValue ) );
		$parts	= preg_split( '/\s*;\s*/', $string );
		$value	= array_shift( $parts );
		$list	= array();
		if( 0 !== count( $parts ) ){
			foreach( $parts as $part ){
				if( preg_match( '/=/', $part ) ){
					$p = preg_split( '/\s?=\s?/', $part, 2 );
					if( trim( $p[1][0] ) === '"' )
						$p[1]	= substr( trim( $p[1] ), 1, -1 );
					if( preg_match( '/\*\d+\*?$/', $p[0] ) ){
						$label	= preg_replace( '/^(.+)\*\d+\*?$/', '\\1', $p[0] );
						if( !isset( $list[$label] ) )
							$list[$label]	= '';
						$list[$label]	.= $p[1];
					}
					else
						$list[$p[0]]	= $p[1];
				}
			}
		}

		//  Apply RFC 2231 (https://datatracker.ietf.org/doc/html/rfc2231)
		$rfc2231	= "/^(?<charset>[A-Z0-9\-]+)(\'(?<language>[A-Z\-]{0,5})\')(?<content>.*)$/i";	//  eG. utf-8'en'Some%20content
		array_walk( $list, function( &$value, $key ) use ($rfc2231): void{								//  apply RFC expr to list
			if( $r = preg_match( $rfc2231, $value, $matches ) ){									//  encoding prefix found
				$m	= (object) $matches;															//  shortcut matches
				if( strtoupper( $m->charset ) !== 'UTF-8' )											//  encoding differs from UTF-8
					$m->content    = iconv( $m->charset, 'UTF-8', $m->content );					//  recode content to UTF-8
				$value = urldecode( $m->content );													//  remove prefix and decode content
			}
		} );

		return (object) array(
			'value'			=> $value,
			'attributes'	=> new \ADT_List_Dictionary( $list ),
		);
	}

	/**
	 *	...
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Header fields block to parse
	 *	@param		integer		$mode			iconv mode (0-normal, 1-strict, 2-tolerant), default:
	 *	@return		Section
 	 */
	public static function parseByIconvStrategy( $content, $mode = 0 ): Section
	{
		$headers	= iconv_mime_decode_headers( $content, $mode, 'UTF-8' );
		$section	= new Section();
		foreach( $headers as $key => $values ){
			if( !is_array( $values ) )
				$values	= [$values];
			foreach( $values as $value ){
				$field	= new Field( $key, $value );
				$section->addField( $field );
			}
		}
		return $section;
	}

	public static function parseByThirdStrategy( string $content ): Section
	{
		$section	= new Section();
		$lines		= preg_split( "/\r?\n/", $content );
		$fws		= '';
		$buffer		= [];
		$field		= NULL;
		foreach( $lines as $nr => $line ){
			if( preg_match( '/^\S+:/', $line ) ){
				list( $key, $value ) = explode( ':', $line, 2 );
				$value	= Encoding::decodeIfNeeded( ltrim( $value ) );
				$field	= new Field( $key, $value );
				$section->addField( $field );
				$buffer	= [$value];
				$fws	= '';
			}
			else if( NULL !== $field ){											//  line is folded line
				if( mb_strlen( $fws ) === 0 )									//  folding white space not detected yet
					$fws	= preg_replace( '/^(\s+).+$/', '\\1', $line );		//  get only folding white space
				$reducedLine	= substr( $line, strlen( $fws ) );				//  reduce line by folding white space
				if( preg_match( '/^\s/', $reducedLine ) )						//  reduced line still contains leading white space
					$buffer[]	= ltrim( $line );								//  folding @ level 2: folded structure header field
				else{															//  reduced line is folding @ level 1
					$line		= ' '.ltrim( $line );							//  reduce leading white space to one
					$buffer[]	= Encoding::decodeIfNeeded( $line );			//  collect decoded line
				}
				$field->setValue( join( $buffer ) );							//  set unfolded field value
			}
		}
		return $section;
	}

	public static function parseByFirstStrategy( string $content ): Section
	{
		$section	= new Section();
		$content	= preg_replace( "/\r?\n[\t ]+/", '', $content );				//  unfold field values
		$lines		= preg_split( "/\r?\n/", $content );						//  split header fields
		foreach( $lines as $line ){
			$parts	= explode( ":", $line, 2 );
			if( count( $parts ) > 1 ){
				$value	= trim( $parts[1] );
				if( substr( $value, 0, 2 ) == "=?" )
					$value	= Encoding::decodeIfNeeded( $value );
				$section->addFieldPair( $parts[0], $value );
			}
		}
		return $section;
	}

	public static function parseBySecondStrategy( string $content ): Section
	{
		$section	= new Section();
		$rawPairs	= self::splitIntoListOfUnfoldedDecodedDataObjects( $content );
		foreach( $rawPairs as $rawPair )
			$section->addFieldPair( $rawPair->key, $rawPair->value );
		return $section;
	}

	public function setStrategy( int $strategy ): self
	{
		if( !in_array( $strategy, self::STRATEGIES, TRUE ) )
			throw new \RangeException( 'Invalid strategy' );
		$this->strategy	= $strategy;
		return $this;
	}

	public static function splitIntoListOfUnfoldedDecodedDataObjects( string $content ): array
	{
		$key		= NULL;
		$value		= NULL;
		$list		= array();
		$buffer		= array();
		$lines		= preg_split( "/\r?\n/", $content );
		foreach( $lines as $line ){
			$value	= ltrim( $line );
			if( preg_match( '/^\S/', $line ) > 0 ){
				$parts	= explode( ":", $line, 2 );
				if( !is_null( $key ) && count( $buffer ) > 0 ){
					$list[]	= (object) ['key' => $key, 'value' => join( $buffer )];
					$buffer	= array();
				}
				$key	= $parts[0];
				$value	= ltrim( $parts[1] );
			}
			$value		= preg_replace( '/[\r\n\t]*/', '', $value );
			$buffer[]	= trim( $value );
			$value		= Encoding::decodeIfNeeded( $value );
		}
		if( !is_null( $key ) && count( $buffer ) > 0 )
			$list[]	= (object) ['key' => $key, 'value' => join( $buffer )];
		return $list;
	}
}
