<?php
namespace CeusMedia\MailTest\Message\Signer;

use CeusMedia\Mail\Message\Signer\Dkim;
use CeusMedia\MailTest\TestCase;

use RuntimeException;

class DkimTest extends TestCase
{
	protected string $testKey;

	public function setUp(): void
	{
		parent::setUp();
		//  Generate a test RSA key for DKIM signing
		$this->testKey	= $this->generateTestKey();
	}

	public function testCreateFromKey(): void
	{
		$dkim	= Dkim::createFromKey( $this->testKey, 'example.com', 'default' );
		$this->assertInstanceOf( Dkim::class, $dkim );
	}

	public function testSignMessage(): void
	{
		$dkim	= Dkim::createFromKey( $this->testKey, 'example.com', 'default' );

		$message	= "From: sender@example.com\r\nDate: Mon, 1 Jan 2024 00:00:00 +0000\r\nSubject: Test\r\n\r\nHello World";
		$signed		= $dkim->sign( $message );

		//  Should contain DKIM-Signature header
		$this->assertStringContainsString( 'DKIM-Signature:', $signed );
		$this->assertStringContainsString( 'v=1', $signed );
		$this->assertStringContainsString( 'd=example.com', $signed );
		$this->assertStringContainsString( 's=default', $signed );
		$this->assertStringContainsString( 'a=rsa-sha256', $signed );
		$this->assertStringContainsString( 'c=r/r', $signed );
		$this->assertStringContainsString( 'h=from:date:subject', $signed );
		$this->assertStringContainsString( 'bh=', $signed );
		$this->assertStringContainsString( 'b=', $signed );

		//  Body should be preserved
		$this->assertStringContainsString( 'Hello World', $signed );
	}

	public function testSignMessageWithCustomHeaders(): void
	{
		$dkim	= Dkim::createFromKey( $this->testKey, 'example.com', 'default' );
		$dkim->setHeaders( [ 'from', 'subject' ] );

		$message	= "From: sender@example.com\r\nDate: Mon, 1 Jan 2024 00:00:00 +0000\r\nSubject: Test\r\n\r\nHello World";
		$signed		= $dkim->sign( $message );

		$this->assertStringContainsString( 'h=from:subject', $signed );
	}

	public function testSignMessageWithSimpleCanonicalization(): void
	{
		$dkim	= Dkim::createFromKey( $this->testKey, 'example.com', 'default', 'simple', 'simple' );

		$message	= "From: sender@example.com\r\nDate: Mon, 1 Jan 2024 00:00:00 +0000\r\nSubject: Test\r\n\r\nHello World";
		$signed		= $dkim->sign( $message );

		$this->assertStringContainsString( 'c=s/s', $signed );
	}

	public function testInvalidKeyThrowsException(): void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot parse private key' );
		Dkim::createFromKey( 'not-a-valid-key', 'example.com', 'default' );
	}

	public function testSetAlgorithm(): void
	{
		$dkim	= Dkim::createFromKey( $this->testKey, 'example.com', 'default' );
		$dkim->setAlgorithm( 'rsa-sha256' );

		$message	= "From: sender@example.com\r\nDate: Mon, 1 Jan 2024 00:00:00 +0000\r\nSubject: Test\r\n\r\nHello World";
		$signed		= $dkim->sign( $message );

		$this->assertStringContainsString( 'a=rsa-sha256', $signed );
	}

	protected function generateTestKey(): string
	{
		$config	= [
			'digest_alg'		=> 'sha256',
			'private_key_bits'	=> 1024,
			'private_key_type'	=> OPENSSL_KEYTYPE_RSA,
		];
		$key	= openssl_pkey_new( $config );
		if( FALSE === $key )
			throw new RuntimeException( 'Cannot generate test key' );

		$pem	= '';
		openssl_pkey_export( $key, $pem );
		return $pem;
	}
}

