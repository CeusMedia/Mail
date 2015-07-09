<?php
/**
 *	Validator for Mail addresses.
 *
 *	Copyright (c) 2007-2015 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Check;
/**
 *	Validator for Mail addresses.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Address{

	static protected $regexSimple	= "@^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})$@";
	static protected $regexExtended	= "@^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$@";

	/**
	 *	Tries to validate an mail address and returns found type.
	 *	Types:
	 *		0 - not valid
	 *		1 - simple address
	 *		2 - extended address
	 *
	 *	@static
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@param		boolean		$throwException	Flag: throw exception if invalid, default: TRUE
	 *	@return		integer						0 - not valid | 1 - valid simple address | 2 - valid extended address
	 *	@throws		InvalidArgumentException	if address is not valid and flag 'throwException' is enabled
	 */
	static public function check( $address, $throwException = TRUE ){
		if( preg_match( self::$regexSimple, $address ) ){
			return 1;
		}
		if( preg_match( self::$regexExtended, $address ) ){
			return 2;
		}
		if( $throwException ){
			throw new \InvalidArgumentException( 'Given mail address is not valid' );
		}
		return 0;
	}

	/**
	 *	Indicates whether an address is valid.
	 *	@static
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@return		boolean
	 */
	static public function isValid( $address ){
		return self::check( $address, FALSE ) > 0;
	}
}
?>
