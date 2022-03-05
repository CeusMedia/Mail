<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Renderer as AddressRenderer;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address
 */
class AddressTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{

		$participant	= new Address();
		$this->assertEquals( NULL, $participant->getDomain( FALSE ) );
		$this->assertEquals( NULL, $participant->getLocalPart( FALSE ) );
		$this->assertEquals( NULL, $participant->getName( FALSE ) );

		$participant	= new Address( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName( FALSE ) );

		$participant	= new Address( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );
	}

	/**
	 *	@covers		::create
	 */
/*	public function testCreate()
	{
		$this->markTestSkipped();
		if( version_compare( $this->version, '2.6' ) >= 0 ){
			$this->expectException();
			$this->expectExceptionMessageMatches( '/^Deprecated/' );

		}
		else if( version_compare( $this->version, '2.5' ) >= 0 ){
			$this->expectDeprecation();
			$this->expectDeprecationMessageMatches( '/^Deprecated/' );
		}
		$parser	= Parser::create();
	}*/

	/**
	 *	@covers		::get
	 *	@covers		::__construct
	 */
	public function testGet()
	{
		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>' );
		$this->assertEquals( '"Hans Mustermann" <hans.mustermann@muster-server.tld>', $participant->get() );

		$participant	= new Address( 'Hans_Mustermann <hans_mustermann@muster-server.tld>' );
		$this->assertEquals( 'Hans_Mustermann <hans_mustermann@muster-server.tld>', $participant->get() );

		$participant	= new Address( '<hans.mustermann@muster-server.tld>' );
		$this->assertEquals( 'hans.mustermann@muster-server.tld', $participant->get() );

		$participant	= new Address( 'hans.mustermann@muster-server.tld' );
		$this->assertEquals( 'hans.mustermann@muster-server.tld', $participant->get() );

		$participant	= new Address( 'Christian Würker <christian.wuerker@ceusmedia.de>' );
		$this->assertEquals( '"Christian Würker" <christian.wuerker@ceusmedia.de>', $participant->get() );
	}

	/**
	 *	@covers		::create
	 *	@covers		::getInstance
	 *	@todo		remove coverage of create after removing method
	 */
	public function testGetInstance()
	{
		$address		= 'Hans Mustermann <hans.mustermann@muster-server.tld>';
		$participant	= new Address( $address );
		$instance		= Address::getInstance( $address );

		$this->assertEquals( $participant, $instance );
	}

	/**
	 *	@covers		::getDomain
	 *	@covers		::setDomain
	 */
	public function testGetDomain()
	{
		$participant	= new Address();

		$this->assertEquals( '', $participant->getDomain( FALSE ) );

		$participant->setDomain( 'muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );

		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
	}

	/**
	 *	@covers		::getDomain
	 */
	public function testGetDomain_Exception()
	{
		$this->expectException( 'RuntimeException' );
		$participant	= new Address();
		$participant->getDomain();
	}

	/**
	*	@covers		::getLocalPart
	*	@covers		::setLocalPart
	 */
	public function testGetLocalPart()
	{
		$participant	= new Address();

		$this->assertEquals( '', $participant->getLocalPart( FALSE ) );

		$participant->setLocalPart( 'Hans.Mustermann' );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );

		$participant	= new Address( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
	}

	/**
	 *	@covers		::getLocalPart
	 */
	public function testGetLocalPart_Exception()
	{
		$this->expectException( 'RuntimeException' );
		$participant	= new Address();
		$participant->getLocalPart();
	}

	/**
	*	@covers		::getName
	*	@covers		::setName
	 */
	public function testGetName()
	{
		$participant	= new Address();

		$this->assertEquals( '', $participant->getName( FALSE ) );

		$participant->setName( 'Hans Mustermann' );
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );

		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );

		$participant->setName( 'Christian Würker' );
		$this->assertEquals( 'Christian Würker', $participant->getName() );

		$participant	= new Address( '"Christian Würker" <christian.wuerker@ceusmedia.de>');
		$this->assertEquals( 'Christian Würker', $participant->getName() );
	}

	/**
	 *	@covers		::getName
	 */
	public function testGetNameException()
	{
		$this->expectException( 'RuntimeException' );
		$participant	= new Address( 'hans.mustermann@muster-server.tld' );
		$participant->getName();
	}

	/**
	 *	@covers		::getAddress
	 *	@covers		::set
	 */
	public function testGetAddress()
	{
		$participant	= new Address();
		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName( FALSE ) );
		$this->assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->getAddress() );
	}

	/**
	 *	@covers		::render
	 *	@covers		::__toString
	 */
	public function testRender()
	{
		$participant	= new Address( 'Hans.Mustermann@muster-server.tld' );
		$expected		= $participant->get();

		$actual		= Address::getInstance()->render( $participant );
		$this->assertEquals( $expected, $actual );

		$this->assertEquals( $expected, (string) $participant );
	}

	/**
	 *	@covers		::set
	 *	@covers		::get
	 */
	public function testSet()
	{
		$participant	= new Address();
		$participant->set( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>', $participant->get() );

		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->get() );
	}

	/**
	 *	@covers		::set
	 *	@covers		::get
	 */
	public function testSet_Exception()
	{
		$this->expectException( 'InvalidArgumentException' );
		$participant	= new Address();
		$participant->set( '' );
	}
}
