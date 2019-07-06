<?php
/**
 *	Unit test for mail address availability check.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Check
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Check\Availability;

/**
 *	Unit test for mail address availability check.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Check
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Check\Availability
 */
class Address_Check_AvailabilityTest extends TestCase{

	/**
	 *	@covers		::test
	 */
	public function testTest(){
		$configReceiver	= $this->requireReceiverConfig();

		$participant	= new Address( $configReceiver->get( 'mailbox.address' ) );
		$check			= new Availability( $participant );
//		$check->setVerbose( !TRUE );
		$creation		= $check->test( $participant );
//		$response		= $check->getLastResponse();
		$this->assertTrue( $creation );

		$participant	= new Address( '_not_existing@notexisting123456.org' );
		$check			= new Availability( $participant );
//		$check->setVerbose( !TRUE );
		$creation		= $check->test( $participant );
//		$response		= $check->getLastResponse();
		$this->assertFalse( $creation );
	}
}
