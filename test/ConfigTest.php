<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest;

/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class ConfigTest extends TestCase
{
	public function testSenderConfig(): void
	{
		$configSender	= $this->requireSenderConfig();
		self::assertTrue( true );
#		remark( 'Config: Sender' );
#		print_m( $configSender->getAll() );
	}

	public function testReceiverConfig(): void
	{
		$configReceiver	= $this->requireReceiverConfig();
		self::assertTrue( true );
#		remark( 'Config: Receiver' );
#		print_m( $configReceiver->getAll() );
	}
}
