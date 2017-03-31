<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once __DIR__.'/bootstrap.php';
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class AddressTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct(){

		$participant	= new \CeusMedia\Mail\Address();
		$this->assertEquals( NULL, $participant->getDomain( FALSE ) );
		$this->assertEquals( NULL, $participant->getLocalPart( FALSE ) );
		$this->assertEquals( NULL, $participant->getName( FALSE ) );

		$participant	= new \CeusMedia\Mail\Address( "Hans.Mustermann@muster-server.tld" );
		$this->assertEquals( "muster-server.tld", $participant->getDomain() );
		$this->assertEquals( "Hans.Mustermann", $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName() );

		$participant	= new \CeusMedia\Mail\Address( "Hans Mustermann <Hans.Mustermann@muster-server.tld>" );
		$this->assertEquals( "muster-server.tld", $participant->getDomain() );
		$this->assertEquals( "Hans.Mustermann", $participant->getLocalPart() );
		$this->assertEquals( "Hans Mustermann", $participant->getName() );
	}

	public function testDomain(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->setDomain( "muster-server.tld" );
		$this->assertEquals( "muster-server.tld", $participant->getDomain() );

		$participant	= new \CeusMedia\Mail\Address( "Hans Mustermann <hans.mustermann@muster-server.tld>");
		$this->assertEquals( "muster-server.tld", $participant->getDomain() );
	}

	/**
	 *	@expectedException	RuntimeException
	 */
	public function testDomainException(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->getDomain();
	}

	public function testLocalPart(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->setLocalPart( "Hans.Mustermann" );
		$this->assertEquals( "Hans.Mustermann", $participant->getLocalPart() );

		$participant	= new \CeusMedia\Mail\Address( "Hans Mustermann <Hans.Mustermann@muster-server.tld>");
		$this->assertEquals( "Hans.Mustermann", $participant->getLocalPart() );
	}

	/**
	 *	@expectedException	RuntimeException
	 */
	public function testLocalPartException(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->getLocalPart();
	}

	public function testName(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->setName( "Hans Mustermann" );
		$this->assertEquals( "Hans Mustermann", $participant->getName() );

		$participant	= new \CeusMedia\Mail\Address( "Hans Mustermann <hans.mustermann@muster-server.tld>");
		$this->assertEquals( "Hans Mustermann", $participant->getName() );
	}

	public function testAddress(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->set( "Hans.Mustermann@muster-server.tld" );
		$this->assertEquals( "muster-server.tld", $participant->getDomain() );
		$this->assertEquals( "Hans.Mustermann", $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName() );
		$this->assertEquals( "Hans.Mustermann@muster-server.tld", $participant->getAddress() );
	}

	public function testGetSet(){
		$participant	= new \CeusMedia\Mail\Address();
		$participant->set( "Hans Mustermann <Hans.Mustermann@muster-server.tld>" );
		$this->assertEquals( "Hans Mustermann <Hans.Mustermann@muster-server.tld>", $participant->get() );

		$participant->set( "Hans.Mustermann@muster-server.tld" );
		$this->assertEquals( "Hans.Mustermann@muster-server.tld", $participant->get() );
	}
}
