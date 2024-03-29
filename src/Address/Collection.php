<?php
declare(strict_types=1);

/**
 *	Collection of mail addresses.
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
use CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;
use Countable;
use Iterator;

/**
 *	Collection of mail addresses.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Collection implements Countable, Iterator
{
	/** @var Address[] $list */
	protected $list		= [];

	/** @var int $position */
	protected $position	= 0;

	public function __construct( ?array $addresses = NULL )
	{
		if( NULL !== $addresses )
			foreach( $addresses as $address )
				$this->add( $address );
	}

	public function __toString(): string
	{
		return $this->render();
	}

	public function add( Address $address ): self
	{
		$this->list[]	= $address;
		return $this;
	}

	/**
	 *	Returns Size of Dictionary.
	 *	@access		public
	 *	@return		integer
	 */
	public function count(): int
	{
		return count( $this->list );
	}

	/**
	 *	Returns current Value.
	 *	@access		public
	 *	@return		Address|NULL
	 */
	public function current(): ?Address
	{
		if( $this->position >= $this->count() )
			return NULL;
		$keys	= array_keys( $this->list );
		return $this->list[$this->position];
	}

	public function filter(): self
	{
		$this->list	= array_filter( $this->list );
		return $this;
	}

	public function getAll(): array
	{
		return $this->list;
	}

	/**
	 *	Returns current Key.
	 *	@access		public
	 *	@return		mixed|NULL
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 *	Selects next Pair.
	 *	@access		public
	 *	@return		void
	 */
	public function next()
	{
		$this->position++;
	}

	public function render(): string
	{
		return AddressCollectionRenderer::getInstance()->render( $this );
	}

	/**
	 *	Resets Pair Pointer.
	 *	@access		public
	 *	@return		void
	 */
	public function rewind()
	{
		$this->position	= 0;
	}

	/**
	 *	Returns array of all addresses.
	 *	Eather as address objects or strings (rendered addresses).
	 *	@access		public
	 *	@param		boolean		$renderValues		Return strings (rendered addresses), default: no
	 *	@return		array
	 */
	public function toArray( bool $renderValues = FALSE ): array
	{
		$list	= $this->list;
		if( $renderValues )
			array_walk( $list, function( Address $address ){
				return $address->render();
			} );
		return $list;
	}

	/**
	 *	Indicates whether Pair Pointer is valid.
	 *	@access		public
	 *	@return		boolean
	 */
	public function valid(): bool
	{
		return $this->position < $this->count();
	}
}
