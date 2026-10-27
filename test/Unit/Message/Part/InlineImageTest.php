<?php
/**
 *	Unit test for mail message inline image part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 */

namespace CeusMedia\MailTest\Unit\Message\Part;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Parser as HeaderParser;
use CeusMedia\Mail\Message\Part\InlineImage;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message inline image part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\InlineImage
 */
class InlineImageTest extends TestCase
{
	/**
	 *	File name in Content-Type (name) and Content-Disposition (filename), encoded if needed (RFC 2231).
	 *	@covers		::render
	 */
	public function testRenderFileName(): void
	{
		$fileName	= 'Logo "Grün"; 2026.png';
		$part		= ( new InlineImage( 'logo' ) )
			->setMimeType( 'image/png' )
			->setEncoding( 'base64' )
			->setContent( 'x' )
			->setFileName( $fileName )
			->setFileSize( 1 );
		$headers	= (string) strstr( $part->render(), Message::$delimiter.Message::$delimiter, TRUE );

		self::assertTrue( mb_check_encoding( $headers, 'ASCII' ), 'headers without 8bit characters' );
		self::assertStringContainsString( 'Content-ID: <logo>', $headers );
		self::assertStringContainsString( 'Content-Disposition: inline;'.Message::$delimiter.' filename*=UTF-8', $headers );

		$section	= HeaderParser::getInstance()->parse( $headers );
		self::assertSame( 'image/png', $section->getField( 'Content-Type' )?->getValue() );
		self::assertSame( $fileName, $section->getField( 'Content-Type' )?->getAttribute( 'name' ) );
		self::assertSame( 'inline', $section->getField( 'Content-Disposition' )?->getValue() );
		self::assertSame( $fileName, $section->getField( 'Content-Disposition' )?->getAttribute( 'filename' ) );
		self::assertSame( '1', $section->getField( 'Content-Disposition' )?->getAttribute( 'size' ) );
	}
}
