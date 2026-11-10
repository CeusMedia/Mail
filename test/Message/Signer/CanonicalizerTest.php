<?php
namespace CeusMedia\MailTest\Message\Signer;

use CeusMedia\Mail\Message\Signer\Canonicalizer;
use CeusMedia\MailTest\TestCase;
use InvalidArgumentException;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Message\Signer\Canonicalizer
 */
class CanonicalizerTest extends TestCase
{
	/** Example of RFC 6376, 3.4.5 */
	protected const RFC_HEADERS	= "A: X\r\nB : Y\t\r\n\tZ  \r\n";
	protected const RFC_BODY	= " C \r\nD \t E\r\n\r\n\r\n";

	/**
	 *	@covers		::getMode
	 */
	public function testGetMode(): void
	{
		self::assertSame( 'relaxed/relaxed', ( new Canonicalizer() )->getMode() );
		self::assertSame( 'simple/simple', ( new Canonicalizer( Canonicalizer::SIMPLE, Canonicalizer::SIMPLE ) )->getMode() );
		self::assertSame( 'relaxed/simple', ( new Canonicalizer( 'Relaxed', 'simple' ) )->getMode() );
	}

	/**
	 *	@covers		::__construct
	 */
	public function testInvalidModeThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		new Canonicalizer( 'r', 'r' );
	}

	/**
	 *	@covers		::canonicalizeHeaders
	 *	@covers		::canonicalizeHeaderField
	 */
	public function testRfcExampleRelaxedHeaders(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		self::assertSame( "a:X\r\nb:Y Z\r\n", $canonicalizer->canonicalizeHeaders( self::RFC_HEADERS ) );
	}

	/**
	 *	@covers		::canonicalizeBody
	 */
	public function testRfcExampleRelaxedBody(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		self::assertSame( " C\r\nD E\r\n", $canonicalizer->canonicalizeBody( self::RFC_BODY ) );
	}

	/**
	 *	@covers		::canonicalizeHeaders
	 */
	public function testRfcExampleSimpleHeaders(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::SIMPLE, Canonicalizer::SIMPLE );
		self::assertSame( self::RFC_HEADERS, $canonicalizer->canonicalizeHeaders( self::RFC_HEADERS ) );
	}

	/**
	 *	@covers		::canonicalizeBody
	 */
	public function testRfcExampleSimpleBody(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::SIMPLE, Canonicalizer::SIMPLE );
		self::assertSame( " C \r\nD \t E\r\n", $canonicalizer->canonicalizeBody( self::RFC_BODY ) );
	}

	/**
	 *	Empty body: simple is one CRLF, relaxed is empty (RFC 6376, 3.4.3 and 3.4.4).
	 *	@covers		::canonicalizeBody
	 */
	public function testEmptyBody(): void
	{
		self::assertSame( "\r\n", ( new Canonicalizer( 'simple', 'simple' ) )->canonicalizeBody( "\r\n\r\n" ) );
		self::assertSame( '', ( new Canonicalizer( 'relaxed', 'relaxed' ) )->canonicalizeBody( " \r\n\t\r\n" ) );
		self::assertSame( '', ( new Canonicalizer( 'relaxed', 'relaxed' ) )->canonicalizeBody( '' ) );
	}

	/**
	 *	Body with LF line endings is canonicalized as sent by SMTP (CRLF), missing final line break added.
	 *	@covers		::canonicalizeBody
	 */
	public function testBodyLineEndings(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		self::assertSame( "Line 1\r\nLine 2\r\n", $canonicalizer->canonicalizeBody( "Line 1  \nLine 2\t\n\n\n" ) );
		self::assertSame( "Hello World\r\n", $canonicalizer->canonicalizeBody( 'Hello World' ) );
	}

	/**
	 *	Fields are taken in order of the names, repeated names from the bottom up, missing ones add nothing.
	 *	@covers		::canonicalizeHeaderList
	 *	@covers		::splitHeaderFields
	 *	@covers		::getFieldName
	 */
	public function testCanonicalizeHeaderList(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		$headers		= "Received: one\r\nFrom: sender@example.com\r\nReceived: two\r\nX-Custom: Ignore\r\nSubject: Test\r\n";
		$result			= $canonicalizer->canonicalizeHeaderList( $headers, ['Subject', 'received', 'from', 'received', 'received', 'date'] );
		self::assertSame( "subject:Test\r\nreceived:two\r\nfrom:sender@example.com\r\nreceived:one\r\n", $result );
	}

	/**
	 *	@covers		::splitHeaderFields
	 */
	public function testSplitHeaderFields(): void
	{
		self::assertSame(
			['From: a@example.com', "Subject: folded\n line", 'To: b@example.com'],
			Canonicalizer::splitHeaderFields( "From: a@example.com\nSubject: folded\n line\nTo: b@example.com\n" )
		);
	}
}
