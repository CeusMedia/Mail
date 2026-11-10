<?php
/**
 *	Unit test for Message/Part/Mail.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 */

namespace CeusMedia\MailTest\Unit\Message\Part;

use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Message\Part\Mail;
use CeusMedia\MailTest\TestCase;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Message\Part\Mail
 */
class MailTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstructor(): void
	{
		$raw	= "From: sender@example.org\r\nTo: receiver@example.org\r\nSubject: Inner\r\n\r\nBody";
		$part	= new Mail( $raw );

		self::assertSame( 'message/rfc822', $part->getMimeType() );
		self::assertSame( 'UTF-8', $part->getCharset() );
		self::assertSame( 'base64', $part->getEncoding() );
		self::assertSame( 'fixed', $part->getFormat() );
		self::assertSame( Mail::TYPE_MAIL, $part->getType() );
	}

	/**
	 *	@covers		::__construct
	 */
	public function testConstructorWithCustomEncoding(): void
	{
		$raw	= "From: sender@example.org\r\nSubject: Inner\r\n\r\nBody";
		$part	= new Mail( $raw, 'ISO-8859-1', 'quoted-printable' );

		self::assertSame( 'ISO-8859-1', $part->getCharset() );
		self::assertSame( 'quoted-printable', $part->getEncoding() );
	}

	/**
	 *	@covers		::applyHeaders
	 */
	public function testApplyHeaders(): void
	{
		$part	= new Mail( "From: a@b.com\r\n\r\nx" );
		$section	= new MessageHeaderSection();
		$result	= $part->applyHeaders( $section );

		self::assertSame( $section, $result );
		self::assertNotNull( $section->getField( 'Content-Type' ) );
		self::assertStringContainsString( 'message/rfc822', $section->getField( 'Content-Type' )->getValue() );
		self::assertNotNull( $section->getField( 'Content-Transfer-Encoding' ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderAll(): void
	{
		$raw	= "From: sender@example.org\r\nSubject: Inner\r\n\r\nBody text";
		$part	= new Mail( $raw );
		$rendered	= $part->render( Mail::SECTION_ALL );

		self::assertStringContainsString( 'Content-Type:', $rendered );
		self::assertStringContainsString( 'message/rfc822', $rendered );
		self::assertStringContainsString( 'Content-Transfer-Encoding:', $rendered );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderHeaderOnly(): void
	{
		$part	= new Mail( "From: a@b.com\r\n\r\nx" );
		$rendered	= $part->render( Mail::SECTION_HEADER );

		self::assertStringContainsString( 'Content-Type:', $rendered );
		self::assertStringNotContainsString( 'Body', $rendered );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderContentOnly(): void
	{
		$raw	= "From: sender@example.org\r\nSubject: Inner\r\n\r\nBody text";
		$part	= new Mail( $raw );
		$rendered	= $part->render( Mail::SECTION_CONTENT );

		self::assertStringNotContainsString( 'Content-Type:', $rendered );
		//  Content is base64-encoded
		self::assertMatchesRegularExpression( '/^[A-Za-z0-9+\/=]+$/', trim( $rendered ) );
	}
}
