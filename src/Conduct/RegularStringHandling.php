<?php
declare(strict_types=1);

/**
 *	Trait for regular string handling.
 *
 *	Copyright (c) 2022-2024 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Conduct;

use RuntimeException;

/**
 *	Trait for regular string handling.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
trait RegularStringHandling
{
	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string			$expression
	 *	@param		string			$replacement
	 *	@param		string			$string
	 *	@param		string|NULL		$errorMessage
	 *	@return		string
	 *	@throws		RuntimeException
	 */
	protected static function regReplace( string $expression, string $replacement, string $string, string $errorMessage = NULL ): string
	{
		$result	= preg_replace( $expression, $replacement, $string );
		if( NULL === $result ){
			if( NULL === $errorMessage )
				$errorMessage	= 'Regular replacing failed';
			throw new RuntimeException( $errorMessage );
		}
		return $result;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string			$expression
	 *	@param		string			$string
	 *	@param		string|NULL		$errorMessage
	 *	@param		array|NULL		$matches
	 *	@param		0|256|512|768	$flags
	 *	@param		integer			$offset
	 *	@return		bool
	 */
	protected static function regMatch( string $expression, string $string, ?string $errorMessage = NULL, ?array &$matches = NULL, $flags = 0, int $offset = 0 ): bool
	{
		$result	= preg_match( $expression, $string, $matches, $flags, $offset );
		if( FALSE === $result ){
			if( NULL === $errorMessage)
				$errorMessage	= 'Regular matching failed';
			throw new RuntimeException( $errorMessage );
		}
		return 1 === $result;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string		$expression
	 *	@param		string		$string
	 *	@param		int|NULL	$limit
	 *	@param		string|NULL	$errorMessage
	 *	@return		array
	 */
	protected static function regSplit( string $expression, string $string, ?int $limit = 0, ?string $errorMessage = NULL ): array
	{
		$limit	??= -1;
		$parts	= preg_split( $expression, $string, $limit );
		if( FALSE === $parts || 0 === count( $parts ) ){
			if( NULL === $errorMessage )
				$errorMessage	= 'Regular splitting failed';
			throw new RuntimeException( $errorMessage );
		}
		return $parts;
	}

	protected static function strHasContent( string $string ): bool
	{
		return 0 !== strlen( trim( $string ) );
	}
}
