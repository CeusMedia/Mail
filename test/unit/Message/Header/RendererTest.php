<?php
/**
 *	Unit test for mail message header renderer.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\Renderer;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message header renderer.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Renderer
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
		$this->markTestIncomplete( 'No test defined for Message\\Header\\Renderer::render' );

		$renderer	= Renderer::getInstance();
	}*/
}
