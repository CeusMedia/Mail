<?php
/**
 *	Unit test for mail message renderer.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message;

use CeusMedia\Mail\Message\Renderer;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message renderer.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Renderer
 */
class RendererTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance()
	{
		$instance	= Renderer::getInstance();
		self::assertEquals( new Renderer(), $instance );
	}

	/**
	 *	@covers		::render
	 */
/*	public function testRender()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Renderer' );

		$renderer	= Renderer::getInstance();
	}*/
}
