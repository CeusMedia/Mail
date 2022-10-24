<?php
/**
 *	Unit test for mail address availability check.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Address_Check
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Integration\Address\Check;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Check\Availability;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address availability check.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Address_Check
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Check\Availability
 */
class AvailabilityTest extends TestCase
{
	/**
	 *	@covers		::test
	 *	@covers		::getLastResponse
	 *	@covers		::getMailServers
	 *	@covers		::request
	 */
	public function testTest()
	{
		$configSender	= $this->requireSenderConfig();
		$configReceiver	= $this->requireReceiverConfig();

		$sender			= new Address( $configSender->get( 'mailbox.address' ) );
//		if( $this->getAddressIP( $sender ) !== $this->getCurrentIP() )
//			$this->markTestSkipped( 'Sending server IP mismatches sender host.' );

		$check			= new Availability( $sender, !TRUE );

		$participant	= new Address( $configReceiver->get( 'mailbox.address' ) );

//		$check->setVerbose( TRUE );
		$actual		= $check->test( $participant );
		$this->assertTrue( $actual, $check->getLastResponse()->message );

		$actual		= $check->getLastResponse();
		$expected	= (object) array(
			'error'		=> Availability::ERROR_NONE,
			'code'		=> 250,
			'message'	=> '2.1.5 Ok',
		);
		$this->assertEquals( $expected, $actual );
		$this->assertEquals( Availability::ERROR_NONE, $check->getLastResponse( 'error' ) );
		$this->assertEquals( 250, $check->getLastResponse( 'code' ) );
		$this->assertEquals( '2.1.5 Ok', $check->getLastResponse( 'message' ) );

		$actual		= $check->getLastResponse();
		$expected	= (object) array(
			'error'		=> Availability::ERROR_NONE,
			'code'		=> 250,
			'request'	=> 'QUIT',
			'message'	=> '2.1.5 Ok',
			'response'	=> "250 2.1.5 Ok\r\n",
		);
		$this->assertEquals( $expected, $actual );
		$this->assertEquals( Availability::ERROR_NONE, $check->getLastResponse( 'error' ) );
		$this->assertEquals( 250, $check->getLastResponse( 'code' ) );
		$this->assertEquals( '2.1.5 Ok', $check->getLastResponse( 'message' ) );
		$this->assertEquals( "250 2.1.5 Ok\r\n", $check->getLastResponse( 'response' ) );
		$this->assertEquals( 'QUIT', $check->getLastResponse( 'request' ) );

//		$check->setVerbose( TRUE );
		$participant	= new Address( '_not_existing@'.$participant->getDomain() );
		$this->assertFalse( $check->test( $participant ) );
		$this->assertEquals( Availability::ERROR_RECEIVER_NOT_ACCEPTED, $check->getLastResponse( 'error' ) );

//		$check->setVerbose( TRUE );
		$participant	= new Address( '_not_existing@notexisting123456.org' );
		$this->assertFalse( $check->test( $participant ) );
		$this->assertEquals( Availability::ERROR_MX_RESOLUTION_FAILED, $check->getLastResponse( 'error' ) );

//		$check->setVerbose( TRUE );
		$this->assertFalse( $check->test( '_not_existing@notexisting123456.org' ) );
		$this->assertEquals( Availability::ERROR_MX_RESOLUTION_FAILED, $check->getLastResponse( 'error' ) );

//		$check->setVerbose( TRUE );
		$this->assertFalse( $check->test( '_not_existing@_not_important', 'notexisting123456.org' ) );
		$this->assertEquals( Availability::ERROR_SOCKET_FAILED, $check->getLastResponse( 'error' ) );
	}
}
