<?php
/**
 *	Unit test for mail message header renderer.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Header;

use CeusMedia\Mail\Message\Renderer;
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
	 *	@covers		::render
	 */
	public function testRender()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Header\\Renderer' );

		$renderer	= Renderer::getInstance();
	}
}
