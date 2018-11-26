<?php
/**
 *	Unit test for mail address syntax validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';
/**
 *	Unit test for mail address syntax validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Address_Check_SyntaxTest extends PHPUnit_Framework_TestCase{

/*	public function setUp(){
	}

	public function tearUp(){
	}*/

	public function testCheckValidSimple(){
		$assertion	= 1;
		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckValidExtended(){
		$assertion	= 2;
		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckInvalid(){
		$assertion	= 0;

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "foo.bar.@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "test", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "_____@####.++", FALSE );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckException(){
		$this->expectException( "InvalidArgumentException" );
		\CeusMedia\Mail\Address\Check\Syntax::check( "foo.bar.@example.com" );
	}

	public function testIsValidValidSimple(){
		$assertion	= 1;
		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testIsValidValidExtended(){
		$assertion	= 2;
		$creation	= \CeusMedia\Mail\Address\Check\Syntax::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testIsValidInvalid(){
		$assertion	= FALSE;

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::isValid( "foo.bar.@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::isValid( "@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::isValid( "test" );
		$this->assertEquals( $assertion, $creation );

		$creation	= \CeusMedia\Mail\Address\Check\Syntax::isValid( "_____@####.++" );
		$this->assertEquals( $assertion, $creation );
	}
}
