<?php
declare(strict_types=1);

/**
 *	Renderer for mail addresses.
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

use RuntimeException;

/**
 *	Renderer for mail addresses.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			Finish code documentation
 */
class Renderer
{
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
	 *	Renders full mail address by given parts.
	 *	Creates patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		Address		$address		Address to render
	 *	@return		string		Rendered mail address
	 *	@throws		RuntimeException			If domain is empty
	 *	@throws		RuntimeException			If local part is empty
	 *	@todo		addslashes on name?
	 */
	public function render( Address $address ): string
	{
		$domain		= $address->getDomain();
		$localPart	= $address->getLocalPart();
		$name		= $address->getName( FALSE );
		if( 0 === strlen( trim( $name ) ) )
			return $localPart.'@'.$domain;
		if( 1 !== preg_match( '/^\w+$/', $name ) )
			$name	= '"'.$name.'"';
		return $name.' <'.$localPart.'@'.$domain.'>';
	}
}
