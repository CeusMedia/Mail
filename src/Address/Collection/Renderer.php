<?php
declare(strict_types=1);

/**
 *	Renderer for list of addresses.
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
 *	@package		CeusMedia_Mail_Address_Collection
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Collection;

use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Deprecation;

use InvalidArgumentException;

use function join;
use function strlen;

/**
 *	Parser for list of addresses collected as string.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Collection
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Renderer
{
	/** @var string $delimiter */
	protected $delimiter		= ', ';

	/**
	 *	Static constructor.
	 *	@access			public
	 *	@static
	 *	@return			self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 *	@codeCoverageIgnore
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
	 *	Returns set delimiter.
	 *	@access		public
	 *	@return		string
	 */
	public function getDelimiter(): string
	{
		return $this->delimiter;
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

	public function render( AddressCollection $collection ): string
	{
		$list	= [];
		foreach( $collection->getAll() as $address ){
			$list[]	= $address->get();
		}
		return join( $this->delimiter, $list );
	}

	/**
	 *	Sets delimiter.
	 *	@access		public
	 *	@param		string		$delimiter
	 *	@return		self
	 *	@throws		InvalidArgumentException	if delimiter is whitespace or empty
	 */
	public function setDelimiter( string $delimiter ): self
	{
		if( 0 === strlen( trim( $delimiter ) ) )
			throw new InvalidArgumentException( 'Delimiter cannot be empty or whitespace' );
		$this->delimiter	= $delimiter;
		return $this;
	}
}
