<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Part;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part;
use CeusMedia\Mail\Message\Part\Text;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\Text
 */
class TextTest extends TestCase
{
	protected string $delimiter;
	protected int $lineLength;

	public function setup(): void
	{
		$this->delimiter	= Message::$delimiter;
		$this->lineLength	= Message::$lineLength;
	}

	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$content	= "This is the content.";
		$part		= new Text( $content );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( "UTF-8", $part->getCharset() );
		self::assertEquals( "text/plain", $part->getMimeType() );
		self::assertEquals( "base64", $part->getEncoding() );
		self::assertEquals( "fixed", $part->getFormat() );

		$part		= new Text( $content, 'latin1', 'base64' );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( "latin1", $part->getCharset() );
		self::assertEquals( "base64", $part->getEncoding() );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender()
	{
		$content	= "This is the content with umlauts: äöü.";

		$part		= new Text( $content, 'utf-8' );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/plain; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: base64',
			'',
			$this->wrapContent( base64_encode( $content ) ),
		) );
		self::assertEquals( $expected, $part->render() );

		$part		= new Text( $content, 'utf-8', 'quoted-printable' );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/plain; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: quoted-printable',
			'',
			$this->wrapContent( quoted_printable_encode( $content ) ),
		) );
		self::assertEquals( $expected, $part->render() );

		$part		= new Text( $content );
		$part->setMimeType( "text/PLAIN" );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/PLAIN; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: base64',
			'',
			$this->wrapContent( base64_encode( $content ) ),
		) );
		self::assertEquals( $expected, $part->render() );
	}

	/**
	 *	Unencoded content stays unchanged: no charset conversion, no line breaks within allowed line length.
	 *	Only line breaks become CRLF and lines over 998 octets are broken, between UTF-8 characters.
	 *	@covers		::render
	 */
	public function testRender8bit()
	{
		$longLine	= trim( str_repeat( 'Größenänderung ', 10 ) );
		$part		= new Text( "Grüße\n".$longLine."\rEnde", 'utf-8', '8bit' );
		$expected	= join( $this->delimiter, [
			'Content-Type: text/plain; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: 8bit',
			'',
			'Grüße',
			$longLine,
			'Ende',
		] );
		self::assertEquals( $expected, $part->render() );

		$tooLong	= 'a'.str_repeat( 'ä', 600 );
		$part		= new Text( $tooLong, 'utf-8', '8bit' );
		$lines		= explode( $this->delimiter, $part->render( Text::SECTION_CONTENT ) );
		self::assertSame( $tooLong, join( '', $lines ) );
		foreach( $lines as $line ){
			self::assertLessThanOrEqual( 998, strlen( $line ) );
			self::assertTrue( mb_check_encoding( $line, 'UTF-8' ) );
		}
	}

	protected function wrapContent( string $content ): string
	{
		return rtrim( chunk_split( $content, $this->lineLength, $this->delimiter ), $this->delimiter );
	}

	/**
	 *	Long 8bit lines of other charsets are split, too: by octets, if no UTF-8 boundary is found.
	 *	@covers		::render
	 */
	public function testRender8bitNonUtf8LongLine(): void
	{
		foreach( ["\x80", "\xBF", "\xE4", "\xF0"] as $byte ){
			$part		= new Text( str_repeat( $byte, 2500 ), 'ISO-8859-1', '8bit' );
			$content	= $part->render( Part::SECTION_CONTENT );
			foreach( explode( Message::$delimiter, $content ) as $line )
				self::assertLessThanOrEqual( 998, strlen( $line ) );
			self::assertSame( str_repeat( $byte, 2500 ), str_replace( Message::$delimiter, '', $content ) );
		}
	}

	/**
	 *	7bit content containing non-ASCII is sent quoted-printable, not as 8bit data labelled 7bit (RFC 2045, 2.7).
	 *	ASCII content stays 7bit.
	 *	@covers		::render
	 *	@covers		::applyHeaders
	 */
	public function testRender7bitNonAsciiFallsBackToQuotedPrintable(): void
	{
		$part		= new Text( "Grüße", 'utf-8', '7bit' );
		$rendered	= $part->render();
		self::assertStringContainsString( 'Content-Transfer-Encoding: quoted-printable', $rendered );
		self::assertSame( 1, preg_match( '/^[\x00-\x7F]*$/', $rendered ), 'ASCII only' );
		self::assertSame( "Grüße", quoted_printable_decode( $part->render( Part::SECTION_CONTENT ) ) );
		self::assertSame( '7bit', $part->getEncoding() );

		$part		= new Text( "Gruesse", 'utf-8', '7bit' );
		self::assertStringContainsString( 'Content-Transfer-Encoding: 7bit', $part->render() );
		self::assertStringEndsWith( $this->delimiter.'Gruesse', $part->render() );
	}
}
