<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Renderer as AddressRenderer;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address
 */
class AddressTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstruct(): void
	{

		$participant	= new Address();
		self::assertEquals( NULL, $participant->getDomain( FALSE ) );
		self::assertEquals( NULL, $participant->getLocalPart( FALSE ) );
		self::assertEquals( NULL, $participant->getName( FALSE ) );

		$participant	= new Address( 'Hans.Mustermann@muster-server.tld' );
		self::assertEquals( 'muster-server.tld', $participant->getDomain() );
		self::assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		self::assertEquals( NULL, $participant->getName( FALSE ) );

		$participant	= new Address( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		self::assertEquals( 'muster-server.tld', $participant->getDomain() );
		self::assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		self::assertEquals( 'Hans Mustermann', $participant->getName() );
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
	public function testGet(): void
	{
		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>' );
		self::assertEquals( '"Hans Mustermann" <hans.mustermann@muster-server.tld>', $participant->get() );

		$participant	= new Address( 'Hans_Mustermann <hans_mustermann@muster-server.tld>' );
		self::assertEquals( 'Hans_Mustermann <hans_mustermann@muster-server.tld>', $participant->get() );

		$participant	= new Address( '<hans.mustermann@muster-server.tld>' );
		self::assertEquals( 'hans.mustermann@muster-server.tld', $participant->get() );

		$participant	= new Address( 'hans.mustermann@muster-server.tld' );
		self::assertEquals( 'hans.mustermann@muster-server.tld', $participant->get() );

		$participant	= new Address( 'Christian Würker <christian.wuerker@ceusmedia.de>' );
		self::assertEquals( '"Christian Würker" <christian.wuerker@ceusmedia.de>', $participant->get() );
	}

	/**
	 *	@covers		::create
	 *	@covers		::getInstance
	 *	@todo		remove coverage of create after removing method
	 */
	public function testGetInstance(): void
	{
		$address		= 'Hans Mustermann <hans.mustermann@muster-server.tld>';
		$participant	= new Address( $address );
		$instance		= Address::getInstance( $address );

		self::assertEquals( $participant, $instance );
	}

	/**
	 *	@covers		::getDomain
	 *	@covers		::setDomain
	 */
	public function testGetDomain(): void
	{
		$participant	= new Address();

		self::assertEquals( '', $participant->getDomain( FALSE ) );

		$participant->setDomain( 'muster-server.tld' );
		self::assertEquals( 'muster-server.tld', $participant->getDomain() );

		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		self::assertEquals( 'muster-server.tld', $participant->getDomain() );
	}

	/**
	 *	@covers		::getDomain
	 */
	public function testGetDomain_Exception(): void
	{
		$this->expectException( 'RuntimeException' );
		$participant	= new Address();
		$participant->getDomain();
	}

	/**
	*	@covers		::getLocalPart
	*	@covers		::setLocalPart
	 */
	public function testGetLocalPart(): void
	{
		$participant	= new Address();

		self::assertEquals( '', $participant->getLocalPart( FALSE ) );

		$participant->setLocalPart( 'Hans.Mustermann' );
		self::assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );

		$participant	= new Address( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>');
		self::assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
	}

	/**
	 *	@covers		::getLocalPart
	 */
	public function testGetLocalPart_Exception(): void
	{
		$this->expectException( 'RuntimeException' );
		$participant	= new Address();
		$participant->getLocalPart();
	}

	/**
	*	@covers		::getName
	*	@covers		::setName
	 */
	public function testGetName(): void
	{
		$participant	= new Address();

		self::assertEquals( '', $participant->getName( FALSE ) );

		$participant->setName( 'Hans Mustermann' );
		self::assertEquals( 'Hans Mustermann', $participant->getName() );

		$participant	= new Address( 'Hans Mustermann <hans.mustermann@muster-server.tld>');
		self::assertEquals( 'Hans Mustermann', $participant->getName() );

		$participant->setName( 'Christian Würker' );
		self::assertEquals( 'Christian Würker', $participant->getName() );

		$participant	= new Address( '"Christian Würker" <christian.wuerker@ceusmedia.de>');
		self::assertEquals( 'Christian Würker', $participant->getName() );
	}

	/**
	 *	@covers		::getName
	 */
	public function testGetNameException(): void
	{
		$this->expectException( 'RuntimeException' );
		$participant	= new Address( 'hans.mustermann@muster-server.tld' );
		$participant->getName();
	}

	/**
	 *	@covers		::getAddress
	 *	@covers		::set
	 */
	public function testGetAddress(): void
	{
		$participant	= new Address();
		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		self::assertEquals( 'muster-server.tld', $participant->getDomain() );
		self::assertEquals( 'Hans.Mustermann', $participant->getLocalPart() );
		self::assertEquals( NULL, $participant->getName( FALSE ) );
		self::assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->getAddress() );
	}

	/**
	 *	@covers		::render
	 *	@covers		::__toString
	 */
	public function testRender(): void
	{
		$participant	= new Address( 'Hans.Mustermann@muster-server.tld' );
		$expected		= $participant->get();

		$actual		= Address::getInstance( $participant )->render();
		self::assertEquals( $expected, $actual );

		self::assertEquals( $expected, (string) $participant );
	}

	/**
	 *	@covers		::set
	 *	@covers		::get
	 */
	public function testSet(): void
	{
		$participant	= new Address();
		$participant->set( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		self::assertEquals( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>', $participant->get() );

		$participant->set( 'Hans.Mustermann@muster-server.tld' );
		self::assertEquals( 'Hans.Mustermann@muster-server.tld', $participant->get() );
	}

	/**
	 *	@covers		::set
	 *	@covers		::get
	 */
	public function testSet_Exception(): void
	{
		$this->expectException( 'InvalidArgumentException' );
		$participant	= new Address();
		$participant->set( '' );
	}
}
