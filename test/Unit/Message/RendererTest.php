<?php
/**
 *	Unit test for mail message renderer.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding;
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
	 *	Line breaks in subject become a space, they would start a new header (header injection).
	 *	@covers		::render
	 */
	public function testRenderSubjectWithoutLineBreaks()
	{
		$message	= Message::getInstance()
			->setSender( 'sender@example.org' )
			->addRecipient( 'receiver@example.org' )
			->setSubject( "Hello\r\nBcc: victim@example.org\n\nbody" )
			->addText( 'Text' );
		$rendered	= Renderer::render( $message );
		$headers	= (string) strstr( $rendered, Message::$delimiter.Message::$delimiter, TRUE );

		self::assertDoesNotMatchRegularExpression( '/^Bcc:/mi', $headers );
		self::assertSame( 1, preg_match( '/^Subject: ([^\r\n]*(?:\r\n[ \t][^\r\n]*)*)/m', $headers, $matches ) );
		self::assertSame( 'Hello Bcc: victim@example.org body', Encoding::getInstance()->decodeIfNeeded( $matches[1] ) );
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
