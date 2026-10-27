<?php
/**
 *	Unit test for mail message header renderer.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Renderer;
use CeusMedia\MailTest\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 *	Unit test for mail message header renderer.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
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
		self::assertEquals( new Renderer(), $instance );
	}

	/**
	 *	Folded values (line delimiter followed by space or tab) are kept.
	 *	@covers		::renderField
	 */
	public function testRenderFieldKeepsFolding()
	{
		$value	= 'a@example.org,'.Message::$delimiter.' b@example.org,'.Message::$delimiter."\tc@example.org";
		self::assertSame( 'To: '.$value, Renderer::renderField( new Field( 'To', $value ), TRUE ) );
	}

	/**
	 *	@return		array<string,array{string}>
	 */
	public static function injectedValues(): array
	{
		return [
			'CRLF'					=> ["Hello\r\nBcc: victim@example.org"],
			'LF'					=> ["Hello\nBcc: victim@example.org"],
			'CR'					=> ["Hello\rBcc: victim@example.org"],
			'empty line, body'		=> ["Hello\r\n\r\nInjected body"],
			'trailing CRLF'			=> ["Hello\r\n"],
			'fold, then CRLF'		=> ["Hello\r\n world\r\nBcc: victim@example.org"],
			'whitespace line'		=> ["Hello\r\n \r\nBcc: victim@example.org"],
		];
	}

	/**
	 *	Line breaks, which are not folding, would start a new header (header injection).
	 *	@covers		::renderField
	 *	@covers		::checkLineBreaks
	 */
	#[DataProvider( 'injectedValues' )]
	public function testRenderFieldRejectsLineBreaks( string $value )
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Header field "X-Custom" contains line break' );
		Renderer::renderField( new Field( 'X-Custom', $value ), TRUE );
	}

	/**
	 *	@covers		::renderField
	 *	@covers		::checkLineBreaks
	 */
	public function testRenderFieldEncodesLineBreakInAttribute()
	{
		$field	= new Field( 'Content-Disposition', 'attachment', ['filename' => "a.txt\r\nBcc: victim@example.org"] );
		$actual	= Renderer::renderField( $field, TRUE );
		self::assertSame( "Content-Disposition: attachment;\r\n filename*=UTF-8''a.txt%0D%0ABcc%3A%20victim%40example.org", $actual );
	}

	/**
	 *	Attributes: ASCII quoted, non-ASCII encoded (RFC 2231), parsed back unchanged.
	 *	@covers		::renderField
	 */
	public function testRenderFieldAttributes()
	{
		$field	= new Field( 'Content-Type', 'application/pdf', ['name' => 'Größe "1".pdf', 'x-note' => 'a "b"'] );
		$actual	= Renderer::renderField( $field, TRUE );
		self::assertSame( "Content-Type: application/pdf; name*=UTF-8''Gr%C3%B6%C3%9Fe%20%221%22.pdf;\r\n x-note=\"a \\\"b\\\"\"", $actual );
		foreach( explode( "\r\n", $actual ) as $line )
			self::assertLessThanOrEqual( 76, strlen( $line ) );
		$parsed	= \CeusMedia\Mail\Message\Header\Parser::getInstance()->parse( $actual )->getField( 'Content-Type' );
		self::assertSame( ['name' => 'Größe "1".pdf', 'x-note' => 'a "b"'], $parsed?->getAttributes() );
	}

	/**
	 *	@return		array<string,array{string}>
	 */
	public static function invalidNames(): array
	{
		return [
			'colon'			=> ['X-Evil: yes'],
			'line break'	=> ["X-Evil\r\nBcc"],
			'non-ASCII'		=> ['X-Größe'],
		];
	}

	/**
	 *	Header field names are printable ASCII without colon (RFC 5322, 2.2).
	 *	@covers		::renderField
	 */
	#[DataProvider( 'invalidNames' )]
	public function testRenderFieldRejectsInvalidName( string $name )
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid header field name' );
		Renderer::renderField( new Field( $name, 'value' ), TRUE );
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
