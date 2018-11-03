<?php
/**
 *	Renderer for list of addresses.
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

use \CeusMedia\Mail\Address\Collection as AddressCollection;

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
class Renderer{

	static $delimiter		= ', ';

	static public function render( AddressCollection $collection, $delimiter = NULL ){
		$delimiter	= $delimiter ? $delimiter : static::$delimiter;
		if( !strlen( $delimiter ) )
			throw new \InvalidArgumentException( 'Delimiter cannot be empty of whitespace' );
		$list	= array();
		foreach( $collection->getAll() as $address ){
			$list[]	= $address->get();
		}
		return join( $delimiter, $list );
	}
}