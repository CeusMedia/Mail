<?php
declare(strict_types=1);

/**
 *	String helper: content check and regular expressions, reporting PCRE failures by exception.
 *
 *	Copyright (c) 2022-2025 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Util;

use RuntimeException;

use function count;
use function preg_last_error;
use function preg_last_error_msg;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function trim;

/**
 *	String helper: content check and regular expressions, reporting PCRE failures by exception.
 *	Unlike preg_* functions, a PCRE failure (e.g. exhausted backtrack or stack limit) is not returned like no match.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
final class Str
{
	/**
	 *	Indicates whether string is set and contains other characters than white space.
	 *	@access		public
	 *	@static
	 *	@param		string|NULL		$string			String to check
	 *	@return		bool
	 *	@phpstan-assert-if-true		=string			$string
	 */
	public static function hasContent( ?string $string ): bool
	{
		return NULL !== $string && '' !== trim( $string );
	}

	/**
	 *	Indicates whether regular expression matches string.
	 *	@access		public
	 *	@static
	 *	@param		string			$expression		Regular expression
	 *	@param		string			$string			String to match
	 *	@param		array|NULL		$matches		Optional: matches, like by preg_match
	 *	@param		string|NULL		$errorMessage	Optional: message of exception, completed by PCRE error
	 *	@param-out	array<int|string,string>	$matches
	 *	@return		bool
	 *	@throws		RuntimeException				if PCRE fails, e.g. on exhausted backtrack or stack limit
	 */
	public static function regMatch( string $expression, string $string, ?array &$matches = NULL, ?string $errorMessage = NULL ): bool
	{
		$result	= preg_match( $expression, $string, $matches );
		if( FALSE === $result )
			throw self::createException( $errorMessage ?? 'Regular matching failed' );
		return 1 === $result;
	}

	/**
	 *	Returns all matches of regular expression in string, as list of sets (PREG_SET_ORDER).
	 *	@access		public
	 *	@static
	 *	@param		string			$expression		Regular expression
	 *	@param		string			$string			String to match
	 *	@param		string|NULL		$errorMessage	Optional: message of exception, completed by PCRE error
	 *	@return		array<int,array<int|string,string>>
	 *	@throws		RuntimeException				if PCRE fails, e.g. on exhausted backtrack or stack limit
	 */
	public static function regMatchAll( string $expression, string $string, ?string $errorMessage = NULL ): array
	{
		$matches	= [];
		if( FALSE === preg_match_all( $expression, $string, $matches, PREG_SET_ORDER ) )
			throw self::createException( $errorMessage ?? 'Regular matching failed' );
		return $matches;
	}

	/**
	 *	Replaces by regular expression.
	 *	@access		public
	 *	@static
	 *	@param		string			$expression		Regular expression
	 *	@param		string			$replacement	Replacement
	 *	@param		string			$string			String to replace in
	 *	@param		string|NULL		$errorMessage	Optional: message of exception, completed by PCRE error
	 *	@return		string
	 *	@throws		RuntimeException				if PCRE fails, e.g. on exhausted backtrack or stack limit
	 */
	public static function regReplace( string $expression, string $replacement, string $string, ?string $errorMessage = NULL ): string
	{
		$result	= preg_replace( $expression, $replacement, $string );
		if( NULL === $result )
			throw self::createException( $errorMessage ?? 'Regular replacing failed' );
		return $result;
	}

	/**
	 *	Splits string by regular expression.
	 *	@access		public
	 *	@static
	 *	@param		string			$expression		Regular expression
	 *	@param		string			$string			String to split
	 *	@param		int|NULL		$limit			Optional: maximum number of parts, 0 or NULL: no limit
	 *	@param		string|NULL		$errorMessage	Optional: message of exception, completed by PCRE error
	 *	@return		array<int,string>
	 *	@throws		RuntimeException				if PCRE fails, e.g. on exhausted backtrack or stack limit
	 */
	public static function regSplit( string $expression, string $string, ?int $limit = 0, ?string $errorMessage = NULL ): array
	{
		$parts	= preg_split( $expression, $string, $limit ?? -1 );
		if( FALSE === $parts || 0 === count( $parts ) )
			throw self::createException( $errorMessage ?? 'Regular splitting failed' );
		return $parts;
	}

	/*  --  PRIVATE  --  */

	/**
	 *	Creates exception for failed PCRE function, with reason, e.g. "JIT stack limit exhausted".
	 *	@access		private
	 *	@static
	 *	@param		string		$message		Message, completed by PCRE error
	 *	@return		RuntimeException
	 */
	private static function createException( string $message ): RuntimeException
	{
		return new RuntimeException( $message.': '.preg_last_error_msg(), preg_last_error() );
	}
}
