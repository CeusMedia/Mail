<?php
/**
 *	Collection of mail addresses.
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
 *	@package		CeusMedia_Mail_Parser
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address;

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;

class Collection implements \Countable, \Iterator{

/**
 *	Collection of mail addresses.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Parser
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
	protected $list		= array();
	protected $position	= 0;

	public function __construct( $addresses = array() ){
		if( $addresses )
			foreach( $addresses as $address )
				$this->add( $address );
	}

	public function __toString(){
		return $this->render();
	}

	public function add( Address $address ){
		$this->list[]	= $address;
	}

	/**
	 *	Returns Size of Dictionary.
	 *	@access		public
	 *	@return		integer
	 */
	public function count()
	{
		return count( $this->list );
	}

	/**
	 *	Returns current Value.
	 *	@access		public
	 *	@return		mixed
	 */
	public function current()
	{
		if( $this->position >= $this->count() )
			return NULL;
		$keys	= array_keys( $this->list );
		return $this->list[$this->position];
	}

	public function getAll(){
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

	public function render(){
		return AddressCollectionRenderer::create()->render( $this );
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

	public function toArray( $renderValues = FALSE ){
		$list	= $this->list;
		if( $renderValues ){
			$list	= array();
			foreach( $this->list as $address )
				$list[]	= $address->get();
		}
		return $list;
	}

	/**
	 *	Indicates whether Pair Pointer is valid.
	 *	@access		public
	 *	@return		boolean
	 */
	public function valid()
	{
		return $this->position < $this->count();
	}
}
