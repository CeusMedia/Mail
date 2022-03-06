<?php
declare(strict_types=1);

/**
 *	Validator for mail address syntax.
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
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Check;

use CeusMedia\Mail\Conduct\RegularStringHandling;

use ADT_Bitmask;
use Alg_Object_Constant;
use InvalidArgumentException;

use function filter_var;

/**
 *	Validator for mail address syntax.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Syntax
{
	use RegularStringHandling;

	public const MODE_AUTO				= 0;
	public const MODE_ALL				= 1;
	public const MODE_FILTER			= 2;
	public const MODE_SIMPLE_REGEX		= 4;
	public const MODE_EXTENDED_REGEX	= 8;

	/**	@var int $mode */
	protected $mode				= self::MODE_FILTER;

	/**	@var string $regexSimple */
	protected $regexSimple		= "@^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})$@";

	/**	@var string $regexExtended */
	protected $regexExtended	= "@^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$@";

	/**
	 *	Constructor. Sets mode if given.
	 *	@access		public
	 *	@param		integer|NULL	$mode		Optional: Mode to apply, see class constants
	 */
	public function __construct( ?int $mode = NULL )
	{
		if( $mode !== NULL )
			$this->setMode( $mode );
	}

	/**
	 *	Validate an mail address against set mode and returns bitmask of successfully applied test modes.
	 *	@access		public
	 *	@param		string			$address		Mail address to validate
	 *	@param		boolean			$throwException	Flag: throw exception if invalid, default: TRUE
	 *	@return		integer							Bitmask of successfully applied test modes
	 *	@throws		InvalidArgumentException		if address is not valid and flag 'throwException' is enabled
	 */
	public function check( string $address, bool $throwException = TRUE ): int
	{
		$result		= 0;
		$modes		= new ADT_Bitmask( $this->mode );
		$wildcard	= $modes->has( self::MODE_ALL );
		$constants	= Alg_Object_Constant::staticGetAll( self::class, 'MODE_' );

		foreach( $constants as $key => $value ){
			if( $modes->has( $value ) || $wildcard ){
				if( $value === self::MODE_FILTER || $wildcard ){
					if( FALSE !== filter_var( $address, FILTER_VALIDATE_EMAIL ) )
						$result	|= $value;
				}
				if( ( $value === self::MODE_SIMPLE_REGEX ) || $wildcard ){
					if( self::regMatch( $this->regexSimple, $address ) )
						$result	|= $value;
				}
				if( ( $value === self::MODE_EXTENDED_REGEX ) || $wildcard ){
					if( self::regMatch( $this->regexExtended, $address ) )
						$result	|= $value;
				}
			}
		}
		if( 0 === $result && $throwException )
			throw new InvalidArgumentException( 'Given address is not valid' );
		return $result;
	}

	/**
	 *	Tries to validate an mail address and returns found type.
	 *	Types:
	 *		0 - not valid
	 *		1 - filter
	 *		2 - simple address
	 *		3 - extended address
	 *
	 *	@access		public
	 *	@param		string			$address		Mail address to validate
	 *	@param		boolean|NULL	$throwException	Flag: throw exception if invalid, default: TRUE
	 *	@return		integer							Bitmask of successfully applied test modes
	 *	@throws		InvalidArgumentException		if address is not valid and flag 'throwException' is enabled
	 */
	public function evaluate( string $address, ?bool $throwException = TRUE ): int
	{
		$result	= 0;
		$modes	= [
			self::MODE_FILTER,
			self::MODE_SIMPLE_REGEX,
			self::MODE_EXTENDED_REGEX
		];
		foreach( $modes as $mode )
			if( $this->isValidByMode( $address, $mode ) )
				$result	|= $mode;
		return $result;
	}

	/**
	 *	Indicates whether a mode is set.
	 *	@access		public
	 *	@param		integer		$mode			Mode check
	 *	@return		boolean
	 */
	public function isMode( int $mode ): bool
	{
		return $mode === ( $this->mode & $mode );
	}

	/**
	 *	Indicates whether an address is valid.
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@return		boolean
	 */
	public function isValid( string $address ): bool
	{
		return $this->check( $address, FALSE ) > 0;
	}

	/**
	 *	...
	 *	@static
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@param		integer		$mode			Mode to use for validation, see class constants
	 *	@return		boolean
	 *	@todo		implement MODE_AUTO and MODE_ALL
	 */
	public function isValidByMode( string $address, int $mode ): bool
	{
		if( $mode === self::MODE_FILTER )
			return FALSE !== filter_var( $address, FILTER_VALIDATE_EMAIL );
		if( $mode === self::MODE_SIMPLE_REGEX )
			return self::regMatch( $this->regexSimple, $address );
		if( $mode === self::MODE_EXTENDED_REGEX )
			return self::regMatch( $this->regexExtended, $address );
		throw new InvalidArgumentException( 'Invalid mode given' );
	}

	/**
	 *	Sets mode of test to apply to given address.
	 *	@access		public
	 *	@param		integer		$mode		Mode to apply, see class constants
	 *	@return		self
	 */
	public function setMode( int $mode ): self
	{
		$this->mode		= $mode;
		return $this;
	}
}
