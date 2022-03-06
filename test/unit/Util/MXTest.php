<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Util;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Util\MX;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
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
		$this->assertEquals( new MX(), $instance );
	}
}
