<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once __DIR__.'/bootstrap.php';

use \CeusMedia\Mail\Address;

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
	public function testConstruct(){

		$participant	= new Address();
		$this->assertEquals( NULL, $participant->getDomain( FALSE ) );
		$this->assertEquals( NULL, $participant->getLocalPart( FALSE ) );
		$this->assertEquals( NULL, $participant->getName() );

		$participant	= new Address( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName() );

		$participant	= new Address( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );
	}

	/**
	*	@covers		::get
	 *	@covers		::__construct
	 */
	public function testGet(){
		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( '"Hans Mustermann" <hans.mustermann@muster-server.tld>', $participant->get() );

		$participant	= new Address( 'Hans_Mustermann <hans_mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans_Mustermann <hans_mustermann@muster-server.tld>', $participant->get() );

		$participant	= new Address( '<hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'hans.mustermann@muster-server.tld', $participant->get() );

		$participant	= new Address( 'hans.mustermann@muster-server.tld');
		$this->assertEquals( 'hans.mustermann@muster-server.tld', $participant->get() );
	}

	/**
	 *	@covers		::getDomain
	 *	@covers		::setDomain
	 */
	public function testGetDomain(){
		$participant	= new Address();
		$participant->setDomain( 'muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );

		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
	}

	/**
	 *	@covers		::getDomain
	 */
	public function testGetDomain_Exception(){
		$this->expectException( 'RuntimeException' );
		$participant	= new Address();
		$participant->getDomain();
	}

	/**
	*	@covers		::getLocalPart
	*	@covers		::setLocalPart
	 */
	public function testGetLocalPart(){
		$participant	= new Address();
		$participant->setLocalPart( 'Hans.Mustermann' );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );

		$participant	= new Address( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
	}

	/**
	 *	@covers		::getLocalPart
	 */
	public function testGetLocalPart_Exception(){
		$this->expectException( 'RuntimeException' );
		$participant	= new Address();
		$participant->getLocalPart();
	}

	/**
	*	@covers		::getName
	*	@covers		::setName
	 */
	public function testGetName(){
		$participant	= new Address();
		$participant->setName( 'Hans Mustermann' );
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );

		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );
	}

	/**
	*	@covers		::getAddress
	*	@covers		::set
	 */
	public function testGetAddress(){
		$participant	= new Address();
		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName() );
		$this->assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->getAddress() );
	}

	/**
	*	@covers		::set
	*	@covers		::get
	 */
	public function testSet(){
		$participant	= new Address();
		$participant->set( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>', $participant->get() );

		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->get() );
	}
}
