<?php
/**
 *	Parser for list of addresses collected as string.
 *
 *	Copyright (c) 2007-2018 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Parser
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Collection;

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Collection as AddressCollection;
use \CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;

/**
 *	Parser for list of addresses collected as string.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Parser
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Parser{

	const METHOD_AUTO					= 0;
	const METHOD_IMAP					= 1;
	const METHOD_OWN					= 2;
	const METHOD_IMAP_PLUS_OWN			= 3;

	const STATE_SCANNING_FOR_NAME		= 0;
	const STATE_READING_NAME			= 1;
	const STATE_READING_QUOTED_NAME		= 2;
	const STATE_SCANNING_FOR_ADDRESS	= 3;
	const STATE_READING_ADDRESS			= 4;

	static public $method				= 0;

	static public function parse( $string, $delimiter = "," ){
		$method		= static::$method;
//		$hasImap	= function_exists( 'imap_rfc822_parse_adrlist' );
		$hasImap	= extension_loaded( 'imap' );
		/*  downgrade  */
		if( $method === static::METHOD_IMAP_PLUS_OWN && !$hasImap )
			$method = static::METHOD_OWN;
		if( $method === static::METHOD_IMAP && !$hasImap )
			$method = static::METHOD_AUTO;
		/*  upgrade  */
		if( $method === static::METHOD_AUTO && $hasImap )
			$method = static::METHOD_IMAP;
		if( $method === static::METHOD_AUTO )
			$method = static::METHOD_OWN;
		switch( $method ){
			case static::METHOD_IMAP_PLUS_OWN:
				$collection	= static::parseUsingImap( $string );									//  get collection using IMAP functions
				$string		= AddressCollectionRenderer::render( $collection );						//  render collection string
				return static::parseUsingOwn( $string );											//  get collection using own implementation
			case static::METHOD_IMAP:
				return static::parseUsingImap( $string );											//  get collection using IMAP functions
			case static::METHOD_OWN;
				return static::parseUsingOwn( $string );											//  get collection using own implementation
			default:
				throw new \RangeException( 'No supported parser set' );
		}
	}

	static public function parseUsingImap( $string ){
		$string			= trim( $string, '\t\r\n, ' );
		$collection		= new AddressCollection();
		$list			= imap_rfc822_parse_adrlist( $string, '_invalid.tld' );
		foreach( $list as $item ){
			$address	= new Address();
			$address->setLocalPart( $item->mailbox );
			$address->setDomain( $item->host );
			if( !empty( $item->personal ) )
				$address->setName( $item->personal );
			$collection->add( $address );
		}
		return $collection;
	}

	static public function parseUsingOwn( $string, $delimiter = ',' ){
		if( !strlen( $delimiter ) )
			throw new \InvalidArgumentException( 'Delimiter cannot be empty of whitespace' );
		$list		= array();
		$string		= str_replace( "\r", "", str_replace( "\n", "", $string ) );
		if( !strlen( trim( $string ) ) )
			return $list;
		$status		= self::STATE_SCANNING_FOR_NAME;
		$part1		= "";
		$buffer		= "";
		$letters	= str_split( trim( $string ), 1 );
		while( count( $letters ) ){
			$letter = array_shift( $letters );
			if( $status === self::STATE_SCANNING_FOR_NAME  ){
				if( $letter === '"' ){
					$status = self::STATE_READING_QUOTED_NAME;
					continue;
				}
				if( $letter === " " || $letter === $delimiter )
					continue;
				if( $letter === '<' ){
					$status	= self::STATE_READING_ADDRESS;
					continue;
				}
				$status = self::STATE_READING_NAME;
			}
			else if( $status === self::STATE_READING_NAME ){
				if( $letter === '<' ){
					$part1	= trim( $buffer );
					$buffer	= "";
					$status = self::STATE_READING_ADDRESS;
					continue;
				}
				if( $letter === '@' ){
					$status	= self::STATE_READING_ADDRESS;
				}
			}
			else if( $status === self::STATE_READING_QUOTED_NAME ){
				if( $letter === '"' ){
					$status = self::STATE_SCANNING_FOR_ADDRESS;
					$part1	= $buffer;
					$buffer	= "";
					continue;
				}
			}
			else if( $status === self::STATE_SCANNING_FOR_ADDRESS ){
				if( $letter === " " || $letter === "<" )
					continue;
				$status	= self::STATE_READING_ADDRESS;
			}
			else if( $status === self::STATE_READING_ADDRESS ){
				if( $letter === '>' || $letter === " " || $letter === $delimiter ){
					$list[]	= array( 'fullname' => $part1, 'address' => trim( $buffer ) );
					$part1	= "";
					$buffer	= "";
					$status	= self::STATE_SCANNING_FOR_NAME;
					continue;
				}
			}
			$buffer	.= $letter;
		}
		if( $buffer && $status )
			$list[]	= array( 'fullname' => $part1, 'address' => trim( $buffer ) );
<<<<<<< HEAD:src/Parser/AddressList.php
		$list	= $this->decodeNameIfNeeded( $list );
		if( $splitNameParts ){
			$list	= $this->splitNameParts( $list, $swapCommaSeparatedNameParts );
		}
		return $list;
	}

	public function decodeNameIfNeeded( $list ){
		foreach( $list as $nr => $entry ){
			if( preg_match( "/^=\?/", $entry['fullname'] ) ){
				$list[$nr]['fullname']	=  iconv_mime_decode( $entry['fullname'], 0, 'UTF-8' );
			}
		}
		return $list;
	}

	public function splitNameParts( $list, $swapCommaSeparatedNameParts = FALSE ){
		if( $swapCommaSeparatedNameParts )
			$list	= $this->swapCommaSeparatedNameParts( $list );
		foreach( $list as $nr => $entry ){
			if( preg_match( "/ +/", $entry['fullname'] ) ){
				$parts	= preg_split( "/ +/", $entry['fullname'] );
				$list[$nr]['surname']	= array_pop( $parts );
				$list[$nr]['firstname']	= join( ' ', $parts );
			}
		}
		return $list;
	}

	public function swapCommaSeparatedNameParts( $list ){
		foreach( $list as $nr => $entry ){
			if( preg_match( "/, +/", $entry['fullname'] ) ){
				$parts	= preg_split( "/, +/", $entry['fullname'], 2 );
				$list[$nr]['fullname']	= $parts[1].' '.$parts[0];
			}
=======

		$collection	= new AddressCollection();
		foreach( $list as $entry ){
			$address		= trim( $entry['fullname'].' <'.$entry['address'].'>' );
			$collection->add( new Address( $address ) );
>>>>>>> 2.0:src/Address/Collection/Parser.php
		}
		return $collection;
	}
}
