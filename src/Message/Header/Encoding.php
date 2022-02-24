<?php
declare(strict_types=1);

/**
 *	Mail message header encoder and decoder.
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

use CeusMedia\Mail\Message;

use DomainException;
use InvalidArgumentException;
use RangeException;
use RuntimeException;

use function base64_decode;
use function iconv;
use function iconv_mime_decode;
use function imap_qprint;
use function join;
use function preg_match;
use function preg_split;
use function preg_replace;
use function quoted_printable_decode;
use function quoted_printable_encode;
use function strtolower;
use function strtoupper;
use function str_replace;

/**
 *	Mail message header encoder and decoder.
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
class Encoding
{
	public const DECODE_STRATEGY_IMPL			= 1;
	public const DECODE_STRATEGY_ICONV			= 2;
	public const DECODE_STRATEGY_ICONV_STRICT	= 3;
	public const DECODE_STRATEGY_ICONV_TOLERANT	= 4;

	public const ENCODE_STRATEGY_IMPL			= 1;
	public const ENCODE_STRATEGY_MB				= 2;

	public const DECODE_STRATEGIES		= [
		self::DECODE_STRATEGY_IMPL,
		self::DECODE_STRATEGY_ICONV,
		self::DECODE_STRATEGY_ICONV_STRICT,
		self::DECODE_STRATEGY_ICONV_TOLERANT,
	];

	public const ENCODE_STRATEGIES		= [
		self::DECODE_STRATEGY_IMPL,
		self::ENCODE_STRATEGY_MB,
	];

	/** @var		integer			$decodeStrategy		Decode strategy to use */
	public static $decodeStrategy	= self::DECODE_STRATEGY_ICONV;

	/** @var		integer			$encodeStrategy		Decode strategy to use */
	public static $encodeStrategy	= self::ENCODE_STRATEGY_MB;

	/** @var		string			$charset			Target character set */
	public static $charset			= 'UTF-8';

	/**
	 *	Encodes a mail header value string if needed.
	 *	@static
	 *	@access		public
	 *	@static
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@return		string
	 *	@throws		DomainException				if given encoding is not supported
	 */
	public static function decodeIfNeeded( string $string ): string
	{
		switch( static::$decodeStrategy ){
			case self::DECODE_STRATEGY_ICONV:
				$string	= iconv_mime_decode( $string, 0, static::$charset );
				if( FALSE === $string )
					throw new RuntimeException( 'Decoding failed' );
				return $string;
			case self::DECODE_STRATEGY_ICONV_STRICT:
				$string	= iconv_mime_decode( $string, 1, static::$charset );
				if( FALSE === $string )
					throw new RuntimeException( 'Decoding failed' );
				return $string;
			case self::DECODE_STRATEGY_ICONV_TOLERANT:
				$string	= iconv_mime_decode( $string, 2, static::$charset );
				if( FALSE === $string )
					throw new RuntimeException( 'Decoding failed' );
				return $string;
			case self::DECODE_STRATEGY_IMPL:
				return self::decode( $string );
			default:
				throw new DomainException( 'Invalid strategy' );
		}
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@static
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable (deprecated)
	 *	@param		bool		$fold			Flag: apply folding, default: yes
	 *	@return		string
	 *	@throws		RangeException				if given encoding is not supported
	 */
	public static function encodeIfNeeded( string $string, string $encoding = 'base64', bool $fold = TRUE ): string
	{
		if( 1 === preg_match( "/^[\w\s\.-:#]+$/", $string ) )
			return $string;
		switch( strtolower( $encoding ) ){
			case 'base64':
				if( static::ENCODE_STRATEGY_MB === static::$encodeStrategy )
					return mb_encode_mimeheader( $string, static::$charset, 'B', Message::$delimiter );
				return "=?UTF-8?B?".base64_encode( $string )."?=";
			case 'quoted-printable':
				if( static::ENCODE_STRATEGY_MB === static::$encodeStrategy )
					return mb_encode_mimeheader( $string, static::$charset, 'Q', Message::$delimiter );
				$string		= quoted_printable_encode( $string );
				$string		= str_replace( [ '?', ' ' ], [ '=3F', '_' ], $string );
				$replace	= $fold ? "?=".Message::$delimiter."\t"."=?UTF-8?Q?" : '';
				$string   	= str_replace( '='.Message::$delimiter, $replace, $string );
				return		"=?UTF-8?Q?".$string."?=";
			default:
				throw new RangeException( 'Unsupported encoding: '.$encoding );
		}
	}

	/**
	 *	Sets decoding strategy to apply.
	 *	@static
	 *	@access		public
	 *	@param		integer		$strategy		Decoding strategy, see ::DECODE_STRATEGIES
	 *	@return		void
	 *	@throws		RangeException				if given strategy is not supported
	 */
	public static function setDecodeStrategy( int $strategy )
	{
		if( !in_array( $strategy, static::DECODE_STRATEGIES, TRUE ) )
			throw new RangeException( 'Invalid decoding strategy' );
		static::$decodeStrategy	= $strategy;
	}

	/**
	 *	Sets encoding strategy to apply.
	 *	@static
	 *	@access		public
	 *	@param		integer		$strategy		Encoding strategy, see ::ENCODE_STRATEGIES
	 *	@return		void
	 *	@throws		RangeException				if given strategy is not supported
	 */
	public static function setEncodeStrategy( int $strategy )
	{
		if( !in_array( $strategy, static::ENCODE_STRATEGIES, TRUE ) )
			throw new RangeException( 'Invalid encoding strategy' );
		static::$encodeStrategy	= $strategy;
	}

	/*  --  PROTECTED  --  */

	/**
	 *	...
	 *	@static
	 *	@access		protected
	 *	@param		string		$string			...
	 *	@return		string
	 *	@throws		RuntimeException			if Base64 decoding fails
	 *	@throws		RuntimeException			if quoted-printable decoding fails
	 *	@throws		RuntimeException			if line replacing fails
	 *	@throws		InvalidArgumentException	if encoding detection & support fails
	 */
	protected static function decode( string $string ): string
	{
		$pattern	= "/^(.*)=\?(\S+)\?(\S)\?(.+)\?=(.*)$/sU";
		if( 1 !== preg_match( $pattern, $string ) )
			return $string;
		$matches	= [];
		$list		= [];
		$lines		= preg_split( "@\r?\n\s*@", $string );
		if( FALSE === $lines )
			throw new RuntimeException( 'Splitting of header failed' );
		foreach( $lines as $line ){
			$parts	= [];
			while( 1 === preg_match( $pattern, $line, $parts ) ){
				[$before, $charset, $encoding, $content, $after] = array_slice( $parts, 1 );
				switch( strtolower( $encoding ) ){
					case 'b':
						$content	= base64_decode( $content, TRUE );
						if( FALSE === $content )
							throw new RuntimeException( 'Decoded failed' );
						break;
					case 'q':
						$content	= str_replace( "_", " ", $content );
						if( function_exists( 'imap_qprint' ) ){
							$content	= imap_qprint( $content );
							if( FALSE === $content )
								throw new RuntimeException( 'Decoded failed' );
						}
						else{
							$content	= quoted_printable_decode( $content );
						}
						break;
					default:
						throw new InvalidArgumentException( 'Unsupported encoding: '.$encoding );
				}
				if( strtoupper( $charset ) !== static::$charset )
					$content	= iconv( $charset, static::$charset, $content );
				$newLine	= preg_replace( $pattern, $before.$content.$after, $line );
				if( NULL === $newLine )
					throw new RuntimeException( 'Decoded failed' );
				$line	= $newLine;
			}
			$list[]	= $line;
		}
		return join( $list );
	}
}
