<?php
class Test_Check_Address extends PHPUnit_Framework_TestCase{

	public function setUp(){
	}

	public function tearUp(){
	}

	public function testCheckValidSimple(){
		$assertion	= 1;
		$creation	= CMM_Mail_Check_Address::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckValidExtended(){
		$assertion	= 2;
		$creation	= CMM_Mail_Check_Address::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckInvalid(){
		$assertion	= 0;

		$creation	= CMM_Mail_Check_Address::check( "foo.bar.@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= CMM_Mail_Check_Address::check( "@example.com", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= CMM_Mail_Check_Address::check( "test", FALSE );
		$this->assertEquals( $assertion, $creation );

		$creation	= CMM_Mail_Check_Address::check( "_____@####.++", FALSE );
		$this->assertEquals( $assertion, $creation );
	}

	public function testCheckException(){
		$this->setExpectedException( "InvalidArgumentException" );
		CMM_Mail_Check_Address::check( "foo.bar.@example.com" );
	}

	public function testIsValidValidSimple(){
		$assertion	= 1;
		$creation	= CMM_Mail_Check_Address::check( "foo.bar@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testIsValidValidExtended(){
		$assertion	= 2;
		$creation	= CMM_Mail_Check_Address::check( "foo+bar!@example.com" );
		$this->assertEquals( $assertion, $creation );
	}

	public function testIsValidInvalid(){
		$assertion	= FALSE;

		$creation	= CMM_Mail_Check_Address::isValid( "foo.bar.@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= CMM_Mail_Check_Address::isValid( "@example.com" );
		$this->assertEquals( $assertion, $creation );

		$creation	= CMM_Mail_Check_Address::isValid( "test" );
		$this->assertEquals( $assertion, $creation );

		$creation	= CMM_Mail_Check_Address::isValid( "_____@####.++" );
		$this->assertEquals( $assertion, $creation );
	}
}
