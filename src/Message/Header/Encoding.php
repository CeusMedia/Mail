<?php
/**
 *	Mail message header encoder and decoder.
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

use \CeusMedia\Mail\Message;

/**
 *	Mail message header encoder and decoder.
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
class Encoding
{
	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if given encoding is not supported
	 */
	static public function decodeIfNeeded( string $string ): string
	{
		$pattern	= "/^=\?(\S+)\?(\S)\?(.+)\?=$/s";
		if( !preg_match( $pattern, $string ) )
			return $string;
		$matches	= array();
		$list		= array();
		$lines		= preg_split( "@\r?\n\s*@", $string );
		foreach( $lines as $string ){
			$parts	= array();
			preg_match( $pattern, $string, $parts );
			list( $charset, $encoding, $content ) = array_slice( $parts, 1 );
			switch( strtolower( $encoding ) ){
				case 'b':
					$list[]	= base64_decode( $content );
					break;
				case 'q':
					$content	= str_replace( "_", " ", $content );
					if( function_exists( 'imap_qprint' ) )
						$list[]	= imap_qprint( $content );
					else
						$list[]	= quoted_printable_decode( $content );
					break;
				default:
					throw new \InvalidArgumentException( 'Unsupported encoding: '.$encoding );
			}
		}
		return join( $list );
	}

	/**
	 *	Encodes a mail header value string if needed.
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable (deprecated)
	 *	@return		string
	 *	@throws		\InvalidArgumentException	if given encoding is not supported
	 */
	static public function encodeIfNeeded( string $string, string $encoding = "base64", ?bool $fold = TRUE ): string
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
