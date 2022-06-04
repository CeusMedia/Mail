<?php
/**
 *	Unit test for mail message renderer.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message;

use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message renderer.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
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
		$this->assertEquals( new Renderer(), $instance );
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
