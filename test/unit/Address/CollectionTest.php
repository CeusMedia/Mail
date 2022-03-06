<?php
/**
 *	...
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Address;

use CeusMedia\Mail\Test\TestCase;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection;

/**
 *	...
 *	@category			Test
 *	@package			CeusMedia_Mail_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection
 */
class CollectTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 *	@covers		::add
	 *	@covers		::getAll
	 */
	public function test__construct()
	{
		$collection	= new Collection();
		$expected	= [];
		$this->assertEquals( $expected, $collection->getAll() );

		$expected	= [new Address( 'name@domain.tld' )];
		$collection	= new Collection( $expected );
		$this->assertEquals( $expected, $collection->getAll() );
		$this->assertEquals( ['name@domain.tld'], $collection->toArray() );

		$expected	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$collection	= new Collection( $expected );
		$this->assertEquals( $expected, $collection->getAll() );

		$expected	= [
			'name@domain.tld',
			'"Hans Mustermann" <Hans.Mustermann@muster-server.tld>'
		];
	}

	/**
	 *	@covers		::count
	 */
	public function testCount()
	{
		$expected	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$collection	= new Collection( $expected );
		$this->assertEquals( 2, $collection->count() );
		$this->assertEquals( 2, count( $collection ) );

		$collection->add( new Address( 'new@domain.tld' ) );
		$this->assertEquals( 3, $collection->count() );
		$this->assertEquals( 3, count( $collection ) );
	}

	/**
	 *	@covers		::toArray
	 */
	public function testToArray()
	{
		$addresses	= [
			'name@domain.tld',
			'"Hans Mustermann" <Hans.Mustermann@muster-server.tld>'
		];
		$collection	= new Collection();
		foreach( $addresses as $item )
			$collection->add( new Address( $item ) );

		$this->assertEquals( $addresses, $collection->toArray( TRUE ) );

		$expected	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$this->assertEquals( $expected, $collection->toArray( FALSE ) );
	}

	/**
	 *	@covers		::current
	 *	@covers		::key
	 *	@covers		::next
	 *	@covers		::rewind
	 *	@covers		::valid
	 */
	public function testIterator()
	{
		$collection	= new Collection();
		$foundSomething = FALSE;
		foreach( $collection as $nr => $address ){
			$foundSomething = TRUE;
		}
		$this->assertFalse( $foundSomething );
		$this->assertNull( $collection->current() );

		$addresses	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$collection	= new Collection( $addresses );

		foreach( $collection as $nr => $address ){
			if( $nr === 0 )
				$this->assertEquals( $addresses[$nr], $address );
		}

		foreach( $collection as $nr => $address ){
			if( $nr === 0 )
				$this->assertEquals( $addresses[$nr], $address );
		}
	}

	/**
	 *	@covers		::__toString
	 *	@covers		::render
	 */
	public function testRender()
	{
		$addresses	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$collection	= new Collection( $addresses );
		$expected	= 'name@domain.tld, "Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		$this->assertEquals( $expected, $collection->render() );
		$this->assertEquals( $expected, (string) $collection );
	}
}
