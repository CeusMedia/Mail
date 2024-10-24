<?php
/**
 *	...
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Address;

use CeusMedia\MailTest\TestCase;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection;

/**
 *	...
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection
 */
class CollectionTest extends TestCase
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
		self::assertEquals( $expected, $collection->getAll() );

		$expected	= [new Address( 'name@domain.tld' )];
		$collection	= new Collection( $expected );
		self::assertEquals( $expected, $collection->getAll() );
		self::assertEquals( ['name@domain.tld'], $collection->toArray() );

		$expected	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$collection	= new Collection( $expected );
		self::assertEquals( $expected, $collection->getAll() );

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
		self::assertEquals( 2, $collection->count() );
		self::assertEquals( 2, count( $collection ) );

		$collection->add( new Address( 'new@domain.tld' ) );
		self::assertEquals( 3, $collection->count() );
		self::assertEquals( 3, count( $collection ) );
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

		self::assertEquals( $addresses, $collection->toArray( TRUE ) );

		$expected	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		self::assertEquals( $expected, $collection->toArray( FALSE ) );

		self::assertEquals( $addresses, $collection->toArray( TRUE ) );

		self::assertEquals( $expected, $collection->toArray( FALSE ) );
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
		self::assertFalse( $foundSomething );
		self::assertNull( $collection->current() );

		$addresses	= [
			new Address( 'name@domain.tld' ),
			new Address( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' )
		];
		$collection	= new Collection( $addresses );

		foreach( $collection as $nr => $address ){
			if( $nr === 0 )
				self::assertEquals( $addresses[$nr], $address );
		}

		foreach( $collection as $nr => $address ){
			if( $nr === 0 )
				self::assertEquals( $addresses[$nr], $address );
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
		self::assertEquals( $expected, $collection->render() );
		self::assertEquals( $expected, (string) $collection );
	}
}
