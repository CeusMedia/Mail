<?php
/**
 *	Unit test for mail address availability check.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';
/**
 *	Unit test for mail address availability check.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Address_Check_AvailabilityTest extends PHPUnit_Framework_TestCase{

	public function setUp(){
	}

	public function tearUp(){
	}

	public function testTest(){
		$participant	= new \CeusMedia\Mail\Address( "office@ceusmedia.de" );
		$check			= new \CeusMedia\Mail\Address\Check\Availability( $participant );
		$creation		= $check->test( $participant );
		$this->assertEquals( TRUE, $creation );
	}
}
