<?php
declare(strict_types=1);

/**
 *	Parser for mail addresses.
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
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Deprecation;
//use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;

use InvalidArgumentException;
use RuntimeException;

use function array_keys;
use function implode;
use function preg_replace;
use function stripslashes;
use function trim;

/**
 *	Parser for mail addresses.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			Finish code documentation
 */
class Parser
{
	/**	@var	array		$patterns		Map of understandable patterns (regular expressions) */
	protected static $patterns	= [												//  define name patterns
		'name <local-part@domain>'	=> "/^(.*)\s(<((\S+)@(\S+))>)$/U",			//  full address: name and local-part at domain with (maybe in brackets)
		'<local-part@domain>'		=> "/^<((\S+)@(\S+))>$/U",					//  short address: local-part at domain without name (and no brackets)
		'local-part@domain'			=> "/^((\S+)@(\S+))$/U",					//  short address: local-part at domain without name (and no brackets)
	];

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
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method getInstance instead' );
		return new self();
	}

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
	 *	Parse a mail address and return found parts.
	 *	Reads patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		string		$string			Mail address to parse
	 *	@return		Address		Address object
	 *	@throws		RuntimeException			if unfolding of address failed
	 *	@throws		InvalidArgumentException	if given string is not a valid mail address
	 */
	public function parse( string $string ): Address
	{
		$string		= stripslashes( trim( $string ) );
		$string		= preg_replace( "/\r\n /", " ", $string );					//  unfold @see http://tools.ietf.org/html/rfc822#section-3.1
		if( NULL === $string )
			throw new RuntimeException( 'Unfolding of address failed' );
		$regex1		= self::$patterns['name <local-part@domain>'];				//  get pattern of full address
		$regex2		= self::$patterns['<local-part@domain>'];					//  get pattern of short address
		$regex3		= self::$patterns['local-part@domain'];						//  get pattern of short address
		$name		= '';
		if( 1 === preg_match( $regex1, $string ) ){								//  found full address: with name or in brackets
			$localPart	= preg_replace( $regex1, "\\4", $string );				//  extract local part
			if( NULL === $localPart )
				throw new RuntimeException( 'Extraction of local part failed' );
			$domain		= preg_replace( $regex1, "\\5", $string );				//  extract domain part
			if( NULL === $domain )
				throw new RuntimeException( 'Extraction of domain part failed' );
			$name		= preg_replace( $regex1, "\\1", $string );				//  extract user name
			if( NULL === $name )
				throw new RuntimeException( 'Extraction of name failed' );
			$name		= preg_replace( "/^\"(.+)\"$/", "\\1", trim( $name ) );	//  strip quotes from user name
			if( NULL === $name )
				throw new RuntimeException( 'Unquoting of name failed' );
//			$name		= MessageHeaderEncoding::decodeIfNeeded( $name );
		}
		else if( 1 === preg_match( $regex2, $string ) ){						//  otherwise found short address: neither name nor brackets
			$localPart	= preg_replace( $regex2, "\\2", $string );				//  extract local part
			if( NULL === $localPart )
				throw new RuntimeException( 'Extraction of local part failed' );
			$domain		= preg_replace( $regex2, "\\3", $string );				//  extract domain part
			if( NULL === $domain )
				throw new RuntimeException( 'Extraction of domain part failed' );
		}
		else if( 1 === preg_match( $regex3, $string ) ){						//  otherwise found short address: neither name nor brackets
			$localPart	= preg_replace( $regex3, "\\2", $string );				//  extract local part
			if( NULL === $localPart )
				throw new RuntimeException( 'Extraction of local part failed' );
			$domain		= preg_replace( $regex3, "\\3", $string );				//  extract domain part
			if( NULL === $domain )
				throw new RuntimeException( 'Extraction of domain part failed' );
		}
		else{																	//  not matching any pattern
			$list		= '"'.implode( '" or "', array_keys( self::$patterns ) ).'"';
			$message	= 'Invalid address given (must match '.$list.') - got '.$string ;
			throw new InvalidArgumentException( $message );
		}
		$address	= new Address();
		$address->setDomain( $domain );
		$address->setLocalPart( $localPart );
		$address->setName( $name );
		return $address;
	}
}
