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
class Address_Check_AvailabilityTest extends TestCase{

/*	public function setUp(){
	}

	public function tearUp(){
	}*/

	public function testTest(){
		$configReceiver	= $this->requireReceiverConfig();

		$participant	= new \CeusMedia\Mail\Address( $configReceiver->get( 'mailbox.address' ) );
		$check			= new \CeusMedia\Mail\Address\Check\Availability( $participant );
//		$check->setVerbose( !TRUE );
		$creation		= $check->test( $participant );
//		$response		= $check->getLastResponse();
		$this->assertTrue( $creation );

		$participant	= new \CeusMedia\Mail\Address( '_not_existing@notexisting123456.org' );
		$check			= new \CeusMedia\Mail\Address\Check\Availability( $participant );
//		$check->setVerbose( !TRUE );
		$creation		= $check->test( $participant );
//		$response		= $check->getLastResponse();
		$this->assertFalse( $creation );
	}
}
