<?php
/**
 *	Parser for mail headers.
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use \CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;

/**
 *	Parser for mail headers.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
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

//	protected $defaultStategy	= self::STRATEGY_ICONV_TOLERANT;
	protected $defaultStategy	= self::STRATEGY_THIRD;
	protected $strategy			= self::STRATEGY_AUTO;

	/**
	 *	Static constructor.
	 *	@access			public
	 *	@static
	 *	@return			self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 */
	public static function create(): self
	{
		return new static();
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@param		integer		$strategy		Optional: strategy to set, leaving auto mode
	 *	@return		self
	 */
	public static function getInstance( int $strategy = NULL ): self
	{
		$self	= new static;
		if( !is_null( $strategy ) )
			$this->setStrategy( $strategy );
		return $self;
	}

	public function parse( string $content ): MessageHeaderSection
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

	public static function parseByFirstStrategy( string $content ): MessageHeaderSection
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
		foreach( $lines as $nr => $line ){
			if( preg_match( '/^\S+:/', $line ) ){
				list( $key, $value ) = explode( ':', $line, 2 );
				$value	= Encoding::decodeIfNeeded( ltrim( $value ) );
				$field	= new Field( $key, $value );
				$section->addField( $field );
				$buffer	= [$value];
				$fws	= '';
			}
			else{																//  line is folded line
				if( mb_strlen( $fws ) === 0 )									//  folding white space not detected yet
					$fws	= preg_replace( '/^(\s+).+$/', '\\1', $line );		//  get only folding white space
				$reducedLine	= substr( $line, strlen( $fws ) );				//  reduce line by folding white space
				if( preg_match( '/^\s/', $reducedLine ) )						//  reduced line still contains leading white space
					$buffer[]	= ltrim( $line );								//  folding @ level 2: folded structure header field
				else{															//  reduced line is folding @ level 1
					$line		= ' '.ltrim( $line );							//  reduce leading white space to one
					$buffer[]	= Encoding::decodeIfNeeded( $line );		//  collect decoded line
				}
				$field->setValue( join( $buffer ) );							//  set unfolded field value
			}
		}
		return $section;
	}

	public static function parseByFirstStrategy( string $content ): Section
	{
		$section	= new MessageHeaderSection();
		$content	= preg_replace( "/\r?\n[\t ]+/", "", $content );				//  unfold field values
		$lines		= preg_split( "/\r?\n/", $content );						//  split header fields
		foreach( $lines as $line ){
			$parts	= explode( ":", $line, 2 );
			if( count( $parts ) > 1 ){
				$value	= trim( $parts[1] );
				if( substr( $value, 0, 2 ) == "=?" )
					$value	= MessageHeaderEncoding::decodeIfNeeded( $value );
				$section->addFieldPair( $parts[0], $value );
			}
		}
		return $section;
	}

	public static function parseBySecondStrategy( string $content ): MessageHeaderSection
	{
		$section	= new MessageHeaderSection();
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
		$lines		= preg_split( "/\r?\n/", $content );
		foreach( $lines as $line ){
			$value	= ltrim( $line );
			if( preg_match( '/^\S/', $line[0] ) > 0 ){
				$parts	= explode( ":", $line, 2 );
				if( !is_null( $key ) && count( $buffer ) > 0 ){
					$list[]	= (object) ['key' => $key, 'value' => join( $buffer )];
					$buffer	= array();
				}
				$key	= $parts[0];
				$value	= ltrim( $parts[1] );
			}
			$value		= MessageHeaderEncoding::decodeIfNeeded( $value );
			$value		= preg_replace( '/[\r\n\t]*/', '', $value );
			$buffer[]	= trim( $value );
		}
		if( !is_null( $key ) && count( $buffer ) > 0 )
			$list[]	= (object) ['key' => $key, 'value' => join( $buffer )];
		return $list;
	}
}
