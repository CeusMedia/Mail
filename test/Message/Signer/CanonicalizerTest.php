<?php
namespace CeusMedia\MailTest\Message\Signer;

use CeusMedia\Mail\Message\Signer\Canonicalizer;
use CeusMedia\MailTest\TestCase;

class CanonicalizerTest extends TestCase
{
	public function testGetModeRelaxedRelaxed(): void
	{
		$canonicalizer	= new Canonicalizer();
		$this->assertSame( 'r/r', $canonicalizer->getMode() );
	}

	public function testGetModeSimpleSimple(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::SIMPLE, Canonicalizer::SIMPLE );
		$this->assertSame( 's/s', $canonicalizer->getMode() );
	}

	public function testGetModeRelaxedSimple(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::SIMPLE );
		$this->assertSame( 'r/s', $canonicalizer->getMode() );
	}

	public function testCanonicalizeBodySimple(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::SIMPLE, Canonicalizer::SIMPLE );
		$body			= "Hello\r\nWorld\r\n\r\n";
		$result			= $canonicalizer->canonicalizeBody( $body );
		$this->assertSame( "Hello\nWorld", $result );
	}

	public function testCanonicalizeBodyRelaxed(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		$body			= "Hello  \r\nWorld  \r\n\r\n";
		$result			= $canonicalizer->canonicalizeBody( $body );
		$this->assertSame( "Hello\nWorld", $result );
	}

	public function testCanonicalizeHeadersRelaxed(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		$headers		= "From: sender@example.com\r\nSubject:  Test  Subject\r\n";
		$result			= $canonicalizer->canonicalizeHeaders( $headers );
		$this->assertStringContainsString( 'from:sender@example.com', $result );
		$this->assertStringContainsString( 'subject:Test Subject', $result );
	}

	public function testCanonicalizeHeaderList(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		$headers		= "From: sender@example.com\r\nDate: Mon, 1 Jan 2024 00:00:00 +0000\r\nSubject: Test\r\nX-Custom: Ignore\r\n";
		$result			= $canonicalizer->canonicalizeHeaderList( $headers, [ 'from', 'subject' ] );
		$this->assertStringContainsString( 'from:sender@example.com', $result );
		$this->assertStringContainsString( 'subject:Test', $result );
		$this->assertStringNotContainsString( 'x-custom', $result );
		$this->assertStringNotContainsString( 'date', $result );
	}

	public function testCanonicalizeHeaderListSimple(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::SIMPLE, Canonicalizer::SIMPLE );
		$headers		= "From: sender@example.com\r\nSubject: Test\r\n";
		$result			= $canonicalizer->canonicalizeHeaderList( $headers, [ 'from', 'subject' ] );
		//  Simple mode keeps original case
		$this->assertStringContainsString( 'From:sender@example.com', $result );
	}

	public function testCanonicalizeBodyRelaxedRemovesTrailingWhitespace(): void
	{
		$canonicalizer	= new Canonicalizer( Canonicalizer::RELAXED, Canonicalizer::RELAXED );
		$body			= "Line 1  \nLine 2\t\n\n\n";
		$result			= $canonicalizer->canonicalizeBody( $body );
		$this->assertSame( "Line 1\nLine 2", $result );
	}
}

