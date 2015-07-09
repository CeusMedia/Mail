<?php
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Test_Check_Address extends PHPUnit_Framework_TestCase{

	public function setUp(){
	}

	public function tearUp(){
	}

	public function testCheckValidSimple(){
		$assertion	= 1;
		$creation	= \CeusMedia\Mail\Check\Address::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckValidExtended(){
		$assertion	= 2;
		$creation	= \CeusMedia\Mail\Check\Address::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckInvalid(){
		$assertion	= 0;

		$creation	= \CeusMedia\Mail\Check\Address::check( "foo.bar.@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Check\Address::check( "@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Check\Address::check( "test", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Check\Address::check( "_____@####.++", FALSE );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckException(){
		$this->setExpectedException( "InvalidArgumentException" );
		\CeusMedia\Mail\Check\Address::check( "foo.bar.@example.com" );
	}

	public function testIsValidValidSimple(){
		$assertion	= 1;
		$creation	= \CeusMedia\Mail\Check\Address::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testIsValidValidExtended(){
		$assertion	= 2;
		$creation	= \CeusMedia\Mail\Check\Address::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testIsValidInvalid(){
		$assertion	= FALSE;

		$creation	= \CeusMedia\Mail\Check\Address::isValid( "foo.bar.@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Check\Address::isValid( "@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Check\Address::isValid( "test" );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Check\Address::isValid( "_____@####.++" );
		$this->assertEquals( $assertion, $creation );
	}
}
