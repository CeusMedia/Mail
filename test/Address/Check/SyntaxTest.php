<?php
/**
 *	Unit test for mail address syntax validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';

use \CeusMedia\Mail\Address\Check\Syntax;

/**
 *	Unit test for mail address syntax validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Check\Syntax
 */
class Address_Check_SyntaxTest extends TestCase{

	/**
	 *	@covers		::check
	 */
	public function testCheckValidSimple(){
		$assertion	= 1;
		$creation	= Syntax::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckValidExtended(){
		$assertion	= 2;
		$creation	= Syntax::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckInvalid(){
		$assertion	= 0;

		$creation	= Syntax::check( "foo.bar.@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= Syntax::check( "@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= Syntax::check( "test", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= Syntax::check( "_____@####.++", FALSE );
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckException(){
		$this->expectException( "InvalidArgumentException" );
		Syntax::check( "foo.bar.@example.com" );
	}

	/**
	 *	@covers		::check
	 */
	public function testIsValidValidSimple(){
		$assertion	= 1;
		$creation	= Syntax::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::check
	 */
	public function testIsValidValidExtended(){
		$assertion	= 2;
		$creation	= Syntax::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::isValid
	 */
	public function testIsValidInvalid(){
		$assertion	= FALSE;

		$creation	= Syntax::isValid( "foo.bar.@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= Syntax::isValid( "@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= Syntax::isValid( "test" );
		$this->assertEquals( $assertion, $creation );

		$creation	= Syntax::isValid( "_____@####.++" );
		$this->assertEquals( $assertion, $creation );
	}
}
