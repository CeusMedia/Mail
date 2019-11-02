<?php
/**
 *	Validator for mail address syntax.
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Check;

/**
 *	Validator for mail address syntax.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Syntax
{
	const MODE_AUTO				= 0;
	const MODE_ALL				= 1;
	const MODE_FILTER			= 2;
	const MODE_SIMPLE_REGEX		= 4;
	const MODE_EXTENDED_REGEX	= 8;

	protected $mode				= 2;
	protected $regexSimple		= "@^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})$@";
	protected $regexExtended	= "@^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$@";

	public function __construct( $mode = NULL )
	{
		if( $mode !== NULL )
			$this->setMode( $mode );
	}

	/**
	 *	Validate an mail address against set mode and map of results.
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@param		boolean		$throwException	Flag: throw exception if invalid, default: TRUE
	 *	@return		array						0 - not valid | 1 - valid simple address | 2 - valid extended address
	 *	@throws		\InvalidArgumentException	if address is not valid and flag 'throwException' is enabled
	 */
	public function check( $address, ?bool $throwException = TRUE ): array
	{
		$result		= 0;
		$wildcard	= $this->mode & self::MODE_ALL;
		$constants	= \Alg_Object_Constant::staticGetAll( self::class, 'MODE_' );
		foreach( $constants as $key => $value ){
			$result[$value]	= NULL;
			if( $this->mode & $value || $wildcard ){
				if( $value === self::MODE_FILTER || $wildcard ){
					if( filter_var( $address, FILTER_VALIDATE_EMAIL ) )
						$result	&= $value;
				}
				else if( $value === self::MODE_SIMPLE_REGEX || $wildcard ){
					if( preg_match( $this->regexSimple, $address ) )
						$result	&= $value;
				}
				else if( $value === self::MODE_EXTENDED_REGEX || $wildcard ){
					if( preg_match( $this->regexExtended, $address ) )
						$result	&= $value;
				}
			}
		}
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
	 *	@static
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@param		boolean		$throwException	Flag: throw exception if invalid, default: TRUE
	 *	@return		integer						0 - not valid | 2 - filter | 4 - valid simple address | 8 - valid extended address
	 *	@throws		\InvalidArgumentException	if address is not valid and flag 'throwException' is enabled
	 */
	public function evaluate( $address, $throwException = TRUE ): int
	{
		$result	= 0;
		$modes	= array( self::MODE_FILTER, self::MODE_SIMPLE_REGEX, self::MODE_EXTENDED_REGEX );
		foreach( $modes as $mode )
			if( $this->isValidByMode( $address, $mode ) )
				$result	|= $mode;
		return $result;
	}

	/**
	 *	Indicates whether an address is valid.
	 *	@static
	 *	@access		public
	 *	@param		string		$address		Mail address to validate
	 *	@return		boolean
	 */
	public function isValid( $address ): bool
	{
		return self::check( $address, FALSE ) > 0;
	}

	public function isValidByMode( $address, $mode ): bool
	{
		if( $mode === self::MODE_FILTER )
			return filter_var( $address, FILTER_VALIDATE_EMAIL );
		if( $mode === self::MODE_SIMPLE_REGEX )
			return preg_match( $this->regexSimple, $address );
		if( $mode === self::MODE_EXTENDED_REGEX )
			return preg_match( $this->regexExtended, $address );
		throw new \InvalidArgumentException( 'Invalid mode given' );
	}

	public function setMode( int $mode ): self
	{
		$this->mode		= $mode;
		return $this;
	}
}
