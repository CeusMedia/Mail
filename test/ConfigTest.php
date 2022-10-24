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
	public function testSenderConfig()
	{
		$configSender	= $this->requireSenderConfig();
		$this->assertTrue( true );
#		remark( 'Config: Sender' );
#		print_m( $configSender->getAll() );
	}

	public function testReceiverConfig()
	{
		$configReceiver	= $this->requireReceiverConfig();
		$this->assertTrue( true );
#		remark( 'Config: Receiver' );
#		print_m( $configReceiver->getAll() );
	}
}
