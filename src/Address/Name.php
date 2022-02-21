<?php
declare(strict_types=1);

/**
 *	Address name parser.
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
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Name
{
	/** @var		string|NULL		$firstname */
	protected $firstname;

	/** @var		string|NULL		$fullname */
	protected $fullname;

	/** @var		string|NULL		$surname */
	protected $surname;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string|NULL		$fullname		Fullname, optional
	 *	@param		string|NULL		$surname		Surname, optional
	 *	@param		string|NULL		$firstname		Firstname, optional
	 *	@return		void
	 */
	public function __construct( string $fullname = NULL, string $surname = NULL, string $firstname = NULL )
	{
		if( NULL !== $fullname )
			$this->setFullname( $fullname );
		if( NULL !== $surname )
			$this->setSurname( $surname );
		if( NULL !== $firstname )
			$this->setFirstname( $firstname );
	}

	/**
	 *	Tries to extract firstname and surname for a fullname.
	 *	@access		public
	 *	@static
	 *	@param		Name		$name
	 *	@return		Name
	 */
	public static function splitNameParts( Name $name ): Name
	{
		$fullname	= trim( $name->getFullname() ?? '' );
		$fullname	= preg_replace( '/ +/', ' ', $fullname );
		if( NULL !== $fullname ){
			if( 1 === preg_match( "/ +/", $fullname ) ){
				$name->setFullname( $fullname );
				$parts	= preg_split( "/ +/", $fullname );
				if( FALSE !== $parts && 0 !== count( $parts ) ){
					if( $parts[0] === strtoupper( $parts[0] ) ){
						$surname	= array_shift( $parts );
						$name->setSurname( ucfirst( strtolower( $surname ) ) );
						$name->setFirstname( join( ' ', $parts ) );
					}
					else {
						$name->setSurname( array_pop( $parts ) );
						$name->setFirstname( join( ' ', $parts ) );
					}
				}
			}
		}
		return $name;
	}

	/**
	 *	...
	 *	@access		public
	 *	@static
	 *	@param		Name		$name
	 *	@return		Name
	 */
	public static function swapCommaSeparatedNameParts( Name $name ): Name
	{
		if( 1 === preg_match( "/, +/", $name->getFullname() ?? '' ) ){
			$parts	= preg_split( "/, +/", $name->getFullname() ?? '', 2 );
			if( FALSE !== $parts )
				$name->setFullname( join( ' ', array_reverse( $parts ) ) );
		}
		return $name;
	}

	/**
	 *	Returns firstname.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getFirstname(): ?string
	{
		return $this->firstname;
	}

	/**
	 *	Returns fullname.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getFullname(): ?string
	{
		return $this->fullname;
	}

	/**
	 *	Returns surname.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getSurname(): ?string
	{
		return $this->surname;
	}

	/**
	 *	Sets firstname.
	 *	@access		public
	 *	@param		string		$firstname		Firstname
	 *	@return		self
	 */
	public function setFirstname( string $firstname ): self
	{
		$this->firstname	= $firstname;
		return $this;
	}

	/**
	 *	Sets fullname.
	 *	@access		public
	 *	@param		string		$fullname		Fullname
	 *	@return		self
	 */
	public function setFullname( string $fullname ): self
	{
		$this->fullname	= $fullname;
		return $this;
	}

	/**
	 *	Sets surname.
	 *	@access		public
	 *	@param		string		$surname		Surname
	 *	@return		self
	 */
	public function setSurname( string $surname ): self
	{
		$this->surname	= $surname;
		return $this;
	}
}
