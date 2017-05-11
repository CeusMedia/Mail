<?php
/**
 *	Unit test for mail header field.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once __DIR__.'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class ParticipantTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct(){

		$participant	= new \CeusMedia\Mail\Participant();
		$this->assertEquals( NULL, $participant->getDomain( FALSE ) );
		$this->assertEquals( NULL, $participant->getLocalPart( FALSE ) );
		$this->assertEquals( NULL, $participant->getName( FALSE ) );

		$participant	= new \CeusMedia\Mail\Participant( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName() );

		$participant	= new \CeusMedia\Mail\Participant( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );
	}

	public function testGet(){
		$participant	= new \CeusMedia\Mail\Participant( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( '"Hans Mustermann" <hans.mustermann@muster-server.tld>', $participant->get() );

		$participant	= new \CeusMedia\Mail\Participant( 'Hans_Mustermann <hans_mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans_Mustermann <hans_mustermann@muster-server.tld>', $participant->get() );

		$participant	= new \CeusMedia\Mail\Participant( '<hans.mustermann@muster-server.tld>');
		$this->assertEquals( '<hans.mustermann@muster-server.tld>', $participant->get() );
	}

	public function testGetDomain(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->setDomain( 'muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );

		$participant	= new \CeusMedia\Mail\Participant( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
	}

	/**
	 *	@expectedException	RuntimeException
	 */
	public function testGetDomain_Exception(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->getDomain();
	}

	public function testGetLocalPart(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->setLocalPart( 'Hans.Mustermann' );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );

		$participant	= new \CeusMedia\Mail\Participant( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
	}

	/**
	 *	@expectedException	RuntimeException
	 */
	public function testGetLocalPart_Exception(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->getLocalPart();
	}

	public function testGetName(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->setName( 'Hans Mustermann' );
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );

		$participant	= new \CeusMedia\Mail\Participant( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		$this->assertEquals( 'Hans Mustermann', $participant->getName() );
	}

	public function testGetAddress(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->setAddress( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'muster-server.tld', $participant->getDomain() );
		$this->assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		$this->assertEquals( NULL, $participant->getName() );
		$this->assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->getAddress() );
	}

	public function testGetSet(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->set( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>', $participant->get() );

		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->get() );
	}

	/**
	 *	@expectedException		InvalidArgumentException
	 */
	public function testParseException(){
		$participant	= new \CeusMedia\Mail\Participant();
		$participant->parse( 'invalid' );
	}
}
