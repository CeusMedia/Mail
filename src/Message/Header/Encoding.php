<?php
declare(strict_types=1);

/**
 *	Mail message header encoder and decoder.
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

use \CeusMedia\Mail\Message;
use \DomainException;

/**
 *	Mail message header encoder and decoder.
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
class Encoding
{
	const STRATEGY_IMPL				= 1;
	const STRATEGY_ICONV			= 2;
	const STRATEGY_ICONV_STRICT		= 3;
	const STRATEGY_ICONV_TOLERANT	= 4;

	const STRATEGIES		= [
		self::STRATEGY_IMPL,
		self::STRATEGY_ICONV,
		self::STRATEGY_ICONV_STRICT,
		self::STRATEGY_ICONV_TOLERANT,
	];

	/** @var		integer		$strategy		Decode strategy to use */
	public static $strategy	= self::STRATEGY_ICONV_TOLERANT;

	/**
	 *	Encodes a mail header value string if needed.
	 *	@static
	 *	@access		public
	 *	@static
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if given encoding is not supported
	 */
	public static function decodeIfNeeded( string $string ): string
	{
		switch( self::$strategy ){
			case self::STRATEGY_ICONV:
				return iconv_mime_decode( $string, 0, 'UTF-8' );
			case self::STRATEGY_ICONV_STRICT:
				return iconv_mime_decode( $string, 1, 'UTF-8' );
			case self::STRATEGY_ICONV_TOLERANT:
				return iconv_mime_decode( $string, 2, 'UTF-8' );
			case self::STRATEGY_IMPL:
				return self::decode( $string );
			default:
				throw new DomainException( 'Invalid strategy' );
		}
	}

	protected static function decode( string $string ): string
	{
		$pattern	= "/^(.*)=\?(\S+)\?(\S)\?(.+)\?=(.*)$/sU";
		if( !preg_match( $pattern, $string ) )
			return $string;
		$matches	= array();
		$list		= array();
		$lines		= preg_split( "@\r?\n\s*@", $string );
		foreach( $lines as $line ){
			$parts	= array();
			while( preg_match( $pattern, $line, $parts ) ){
				list( $before, $charset, $encoding, $content, $after ) = array_slice( $parts, 1 );
				switch( strtolower( $encoding ) ){
					case 'b':
						$content	= base64_decode( $content, TRUE );
						if( FALSE === $content )
							throw new \RuntimeException( 'Encoded content contains invalid characters' );
						break;
					case 'q':
						$content	= str_replace( "_", " ", $content );
						if( function_exists( 'imap_qprint' ) )
							$content	= imap_qprint( $content );
						else
							$content	= quoted_printable_decode( $content );
						break;
					default:
						throw new \InvalidArgumentException( 'Unsupported encoding: '.$encoding );
				}
				if( strtoupper( $charset ) !== 'UTF-8' )
					$content	= iconv( $charset, 'UTF-8', $content );
				$line		= preg_replace( $pattern, $before.$content.$after, $line );
			}
			$list[]	= $line;
		}
		return join( $list );
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@static
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable (deprecated)
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if given encoding is not supported
	 */
	public static function encodeIfNeeded( string $string, string $encoding = "base64", ?bool $fold = TRUE ): string
	{
		if( preg_match( "/^[\w\s\.-:#]+$/", $string ) )
			return $string;
		switch( strtolower( $encoding ) ){
			case 'base64':
				return "=?UTF-8?B?".base64_encode( $string )."?=";
			case 'quoted-printable':
				$string		= quoted_printable_encode( $string );
				$string		= str_replace( array( '?', ' '), array( '=3F', '_' ), $string );
				$replace	= $fold ? "?=".Message::$delimiter."\t"."=?UTF-8?Q?" : '';
				$string   	= str_replace( '='.Message::$delimiter, $replace, $string );
				return "=?UTF-8?Q?".$string."?=";
			default:
				throw new \InvalidArgumentException( 'Unsupported encoding: '.$encoding );
		}
	}
}
