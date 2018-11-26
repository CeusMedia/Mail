<?php
namespace CeusMedia\Mail\Address;

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;

class Collection implements \Countable, \Iterator{

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
