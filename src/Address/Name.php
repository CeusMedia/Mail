<?php
declare(strict_types=1);

/**
 *	Address name parser.
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
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address;

use function array_pop;
use function array_reverse;
use function join;
use function preg_match;
use function preg_split;

/**
 *	Address name parser.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Name
{
	static public function splitNameParts( array $list ): array
	{
		foreach( $list as $nr => $entry ){
			if( 1 === preg_match( "/ +/", $entry['fullname'] ) ){
				$parts	= preg_split( "/ +/", $entry['fullname'] );
				if( FALSE !== $parts ){
					$list[$nr]['surname']	= array_pop( $parts );
					$list[$nr]['firstname']	= join( ' ', $parts );
				}
			}
		}
		return $list;
	}

	static public function swapCommaSeparatedNameParts( array $list ): array
	{
		foreach( $list as $nr => $entry ){
			if( 1 === preg_match( "/, +/", $entry['fullname'] ) ){
				$parts	= preg_split( "/, +/", $entry['fullname'], 2 );
				if( FALSE !== $parts )
					$list[$nr]['fullname']	= join( ' ', array_reverse( $parts ) );
			}
		}
		return $list;
	}
}
