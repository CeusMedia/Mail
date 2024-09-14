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
use CeusMedia\Mail\Transport\SMTP\Response as SmtpResponse;
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
	public function testTest(): void
	{
		$configSender	= $this->requireSenderConfig();
		$configReceiver	= $this->requireReceiverConfig();

		$sender			= new Address( $configSender->get( 'mailbox.address' ) );
//		if( $this->getAddressIP( $sender ) !== $this->getCurrentIP() )
//			$this->markTestSkipped( 'Sending server IP mismatches sender host.' );

		$check			= new Availability( $sender, FALSE );

		$participant	= new Address( $configReceiver->get( 'mailbox.address' ) );

//		$check->setVerbose( TRUE );
		$actual		= $check->test( $participant );
		self::assertTrue( $actual, $check->getLastResponse()->message );

		$actual		= $check->getLastResponse();
		$expected	= (object) array(
			'error'		=> SmtpResponse::ERROR_NONE,
			'code'		=> 250,
			'message'	=> '2.1.5 Ok',
		);
		self::assertEquals( $expected, $actual );
		self::assertEquals( SmtpResponse::ERROR_NONE, $check->getLastResponseValue( 'error' ) );
		self::assertEquals( 250, $check->getLastResponseValue( 'code' ) );
		self::assertEquals( '2.1.5 Ok', $check->getLastResponseValue( 'message' ) );

		$actual		= $check->getLastResponse();
		$expected	= (object) array(
			'error'		=> SmtpResponse::ERROR_NONE,
			'code'		=> 250,
			'request'	=> 'QUIT',
			'message'	=> '2.1.5 Ok',
			'response'	=> "250 2.1.5 Ok\r\n",
		);
		self::assertEquals( $expected, $actual );
		self::assertEquals( SmtpResponse::ERROR_NONE, $check->getLastResponseValue( 'error' ) );
		self::assertEquals( 250, $check->getLastResponseValue( 'code' ) );
		self::assertEquals( '2.1.5 Ok', $check->getLastResponseValue( 'message' ) );
		self::assertEquals( "250 2.1.5 Ok\r\n", $check->getLastResponseValue( 'response' ) );
		self::assertEquals( 'QUIT', $check->getLastResponseValue( 'request' ) );

//		$check->setVerbose( TRUE );
		$participant	= new Address( '_not_existing@'.$participant->getDomain() );
		self::assertFalse( $check->test( $participant ) );
		self::assertEquals( SmtpResponse::ERROR_RECEIVER_NOT_ACCEPTED, $check->getLastResponseValue( 'error' ) );

//		$check->setVerbose( TRUE );
		$participant	= new Address( '_not_existing@notexisting123456.org' );
		self::assertFalse( $check->test( $participant ) );
		self::assertEquals( SmtpResponse::ERROR_MX_RESOLUTION_FAILED, $check->getLastResponseValue( 'error' ) );

//		$check->setVerbose( TRUE );
		self::assertFalse( $check->test( '_not_existing@notexisting123456.org' ) );
		self::assertEquals( SmtpResponse::ERROR_MX_RESOLUTION_FAILED, $check->getLastResponseValue( 'error' ) );

//		$check->setVerbose( TRUE );
		self::assertFalse( $check->test( '_not_existing@_not_important', 'notexisting123456.org' ) );
		self::assertEquals( SmtpResponse::ERROR_SOCKET_FAILED, $check->getLastResponseValue( 'error' ) );
	}
}
