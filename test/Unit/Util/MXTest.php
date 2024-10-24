<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Util
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Util;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Util\MX;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Util
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Util\MX
 */
class MXTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance()
	{
		$instance	= MX::getInstance();
		self::assertEquals( new MX(), $instance );
	}
}
