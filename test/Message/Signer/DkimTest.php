<?php
namespace CeusMedia\MailTest\Message\Signer;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Message\Signer\Canonicalizer;
use CeusMedia\Mail\Message\Signer\Dkim;
use CeusMedia\MailTest\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 *	Signatures are checked by DkimVerifier, which is checked itself by the signed message of RFC 8463, Appendix A.
 *	@coversDefaultClass	\CeusMedia\Mail\Message\Signer\Dkim
 */
class DkimTest extends TestCase
{
	/** Public key of RFC 8463, A.2 (selector "test") */
	protected const RFC_PUBLIC_KEY	= 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDkHlOQoBTzWRiGs5V6NpP3idY6Wk08a5qhdR6wy5bdOKb2jLQiY/J16JYi0Qvx/byYzCNb3W91y3FutACDfzwQ/BC/e/8uBsCR+yz1Lxj+PL6lHvqMKrM3rG4hstT5QjvHO9PzoxZyVYLzBfO2EeC3Ip3G+2kryOTIKT+l/K4w3QIDAQAB';

	protected string $dataPath;
	protected string $rsaKey;

	public function setUp(): void
	{
		parent::setUp();
		$this->dataPath	= dirname( __DIR__, 2 ).'/data/dkim/';
		$this->rsaKey	= (string) file_get_contents( $this->dataPath.'rfc8463-rsa.pem' );
	}

	/**
	 *	The verifier accepts the RSA signature of RFC 8463 and detects changes.
	 *	@coversNothing
	 */
	public function testVerifierAcceptsRfc8463Example(): void
	{
		$message	= (string) file_get_contents( $this->dataPath.'rfc8463-message.eml' );
		$result		= DkimVerifier::verify( $message, self::RFC_PUBLIC_KEY, 1 );
		self::assertTrue( $result['bodyHash'] );
		self::assertTrue( $result['signature'] );

		$result		= DkimVerifier::verify( str_replace( 'Joe.', 'Jim.', $message ), self::RFC_PUBLIC_KEY, 1 );
		self::assertFalse( $result['bodyHash'] );
		$result		= DkimVerifier::verify( str_replace( 'dinner', 'lunch', $message ), self::RFC_PUBLIC_KEY, 1 );
		self::assertFalse( $result['signature'] );
	}

	/**
	 *	Body hash of the RFC example is reproduced.
	 *	@covers		::sign
	 */
	public function testBodyHashOfRfc8463Example(): void
	{
		$signed	= $this->createSigner()->sign( $this->getRfcMessage() );
		self::assertStringContainsString( 'bh=2jUSOH9NhtVGCQWNr9BrIAPreKQjO6Sn7XIkfJVOzv8=;', $signed );
	}

	/**
	 *	@return		array<string,array{string,string,string}>
	 */
	public static function provideModes(): array
	{
		return [
			'relaxed/relaxed, CRLF'	=> ['relaxed', 'relaxed', "\r\n"],
			'relaxed/simple, CRLF'	=> ['relaxed', 'simple', "\r\n"],
			'simple/relaxed, CRLF'	=> ['simple', 'relaxed', "\r\n"],
			'simple/simple, CRLF'	=> ['simple', 'simple', "\r\n"],
			'relaxed/relaxed, LF'	=> ['relaxed', 'relaxed', "\n"],
			'simple/simple, LF'		=> ['simple', 'simple', "\n"],
		];
	}

	/**
	 *	Signature is valid, message stays unchanged below the prepended header field.
	 *	@dataProvider	provideModes
	 *	@covers		::sign
	 *	@covers		::renderField
	 *	@covers		::foldSignature
	 */
	public function testSignIsVerifiable( string $headerMode, string $bodyMode, string $eol ): void
	{
		$message	= str_replace( "\r\n", $eol, $this->getRfcMessage() );
		$signed		= $this->createSigner( $headerMode, $bodyMode )->sign( $message );

		self::assertStringStartsWith( 'DKIM-Signature: v=1; a=rsa-sha256; c='.$headerMode.'/'.$bodyMode.';', $signed );
		self::assertStringEndsWith( $eol.$message, $signed );
		self::assertSame( 1, substr_count( $signed, 'DKIM-Signature:' ) );

		$field	= substr( $signed, 0, strlen( $signed ) - strlen( $message ) - strlen( $eol ) );
		foreach( explode( $eol, $field ) as $line )
			self::assertLessThanOrEqual( Dkim::LINE_LENGTH, strlen( $line ) );
		if( "\r\n" === $eol )
			self::assertDoesNotMatchRegularExpression( '/(?<!\r)\n/', $signed );

		$result	= DkimVerifier::verify( $signed, self::RFC_PUBLIC_KEY );
		self::assertTrue( $result['bodyHash'] );
		self::assertTrue( $result['signature'] );
		self::assertSame( 'football.example.com', $result['tags']['d'] );
		self::assertSame( 'test', $result['tags']['s'] );
		self::assertSame( '1528637909', $result['tags']['t'] );
	}

	/**
	 *	A message rendered by the library is signed verifiably, also with folded and encoded headers.
	 *	@covers		::sign
	 */
	public function testSignRenderedMessage(): void
	{
		$message	= Message::getInstance()
			->setSender( 'joe@football.example.com', 'Jörg Sixpäck' )
			->addRecipient( 'suzie@shopping.example.net', 'Suzie Q' )
			->addRecipient( 'cc@example.net', NULL, 'cc' )
			->addRecipient( 'secret@example.net', NULL, 'bcc' )
			->setSubject( 'Ist das Essen fertig? Mit Ümlauten und einem langen Betreff, der gefaltet werden muss' )
			->addText( "Hallo,\r\n\r\nZeile mit  Leerzeichen   \r\n.Punkt am Anfang\r\n" )
			->addHTML( '<p>Hallo</p>' );
		$signed		= $this->createSigner()->sign( Renderer::render( $message ) );

		$result		= DkimVerifier::verify( $signed, self::RFC_PUBLIC_KEY );
		self::assertTrue( $result['bodyHash'] );
		self::assertTrue( $result['signature'] );
		self::assertSame( 'from:from:subject:subject:date:to:cc:message-id:mime-version:content-type', $result['tags']['h'] );
		self::assertStringNotContainsStringIgnoringCase( 'bcc', $result['tags']['h'] );
		self::assertStringNotContainsString( 'secret@example.net', $signed );
	}

	/**
	 *	Only present fields are signed, From and Subject once more (oversigning), so adding one breaks the signature.
	 *	@covers		::sign
	 *	@covers		::getHeaderNamesToSign
	 */
	public function testOversigning(): void
	{
		$signed	= $this->createSigner()->sign( $this->getRfcMessage() );
		$result	= DkimVerifier::verify( $signed, self::RFC_PUBLIC_KEY );
		self::assertSame( 'from:from:subject:subject:date:to:message-id', $result['tags']['h'] );

		$added	= str_replace( "\r\nSubject: ", "\r\nSubject: Win a prize!\r\nSubject: ", $signed );
		self::assertFalse( DkimVerifier::verify( $added, self::RFC_PUBLIC_KEY )['signature'] );

		$signed	= $this->createSigner()->setOversignHeaders( [] )->sign( $this->getRfcMessage() );
		self::assertSame( 'from:subject:date:to:message-id', DkimVerifier::verify( $signed, self::RFC_PUBLIC_KEY )['tags']['h'] );
	}

	/**
	 *	@covers		::setHeaders
	 */
	public function testSetHeaders(): void
	{
		$signed	= $this->createSigner()->setHeaders( ['From', 'Subject', 'X-Missing'] )->sign( $this->getRfcMessage() );
		$result	= DkimVerifier::verify( $signed, self::RFC_PUBLIC_KEY );
		self::assertSame( 'from:from:subject:subject', $result['tags']['h'] );
		self::assertTrue( $result['signature'] );
	}

	/**
	 *	@return		array<string,array{string[]}>
	 */
	public static function provideInvalidHeaders(): array
	{
		return [
			'without From'		=> [['subject', 'date']],
			'with Bcc'			=> [['from', 'bcc']],
			'with signature'	=> [['from', 'DKIM-Signature']],
			'invalid name'		=> [['from', 'x:y']],
		];
	}

	/**
	 *	@dataProvider	provideInvalidHeaders
	 *	@covers		::setHeaders
	 *	@covers		::normalizeHeaderNames
	 *	@param		string[]	$headers
	 */
	public function testSetHeadersInvalid( array $headers ): void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->createSigner()->setHeaders( $headers );
	}

	/**
	 *	@covers		::setOversignHeaders
	 */
	public function testSetOversignHeadersBccThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->createSigner()->setOversignHeaders( ['bcc'] );
	}

	/**
	 *	@covers		::sign
	 */
	public function testSignWithoutFromThrows(): void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'no From header' );
		$this->createSigner()->sign( "Subject: Test\r\n\r\nHello" );
	}

	/**
	 *	8bit data is rejected by default, a conversion to 7bit on the way would break the signature.
	 *	@covers		::sign
	 */
	public function testSign8bitThrows(): void
	{
		$message	= Message::getInstance()
			->setSender( 'joe@football.example.com' )
			->addRecipient( 'suzie@shopping.example.net' )
			->setSubject( 'Test' )
			->addText( 'Grüße', 'UTF-8', '8bit' );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( '8bit' );
		$this->createSigner()->sign( Renderer::render( $message ) );
	}

	/**
	 *	8bit data is signed if allowed, also non-ASCII text encoded by quoted-printable is no 8bit data.
	 *	@covers		::sign
	 *	@covers		::setAllow8bit
	 */
	public function testSign8bitAllowed(): void
	{
		$message	= Message::getInstance()
			->setSender( 'joe@football.example.com' )
			->addRecipient( 'suzie@shopping.example.net' )
			->setSubject( 'Test' );
		$quoted		= Renderer::render( ( clone $message )->addText( 'Grüße', 'UTF-8', 'quoted-printable' ) );
		self::assertTrue( DkimVerifier::verify( $this->createSigner()->sign( $quoted ), self::RFC_PUBLIC_KEY )['signature'] );

		$raw		= Renderer::render( $message->addText( "Grüße\r\n", 'UTF-8', '8bit' ) );
		$signed		= $this->createSigner()->setAllow8bit( TRUE )->sign( $raw );
		$result		= DkimVerifier::verify( $signed, self::RFC_PUBLIC_KEY );
		self::assertTrue( $result['bodyHash'] );
		self::assertTrue( $result['signature'] );
	}

	/**
	 *	@covers		::getDnsRecord
	 *	@covers		::getDnsName
	 */
	public function testDnsRecord(): void
	{
		$signer	= $this->createSigner();
		self::assertSame( 'v=DKIM1; k=rsa; p='.self::RFC_PUBLIC_KEY, $signer->getDnsRecord() );
		self::assertSame( 'test._domainkey.football.example.com', $signer->getDnsName() );
	}

	/**
	 *	@covers		::__construct
	 *	@covers		::loadPrivateKey
	 */
	public function testConstructWithKeyFile(): void
	{
		$signer	= new Dkim( $this->dataPath.'rfc8463-rsa.pem', 'football.example.com', 'test' );
		self::assertSame( 'v=DKIM1; k=rsa; p='.self::RFC_PUBLIC_KEY, $signer->getDnsRecord() );
	}

	/**
	 *	@covers		::loadPrivateKey
	 */
	public function testConstructWithMissingKeyFileThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		new Dkim( $this->dataPath.'missing.pem', 'football.example.com', 'test' );
	}

	/**
	 *	@covers		::__construct
	 */
	public function testInvalidSelectorThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		Dkim::createFromKey( $this->rsaKey, 'football.example.com', 'te st' );
	}

	/**
	 *	@covers		::__construct
	 */
	public function testInvalidModeThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		Dkim::createFromKey( $this->rsaKey, 'football.example.com', 'test', 'r', 'r' );
	}

	/**
	 *	@covers		::setPrivateKeyFromString
	 */
	public function testInvalidKeyThrows(): void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot parse private key' );
		Dkim::createFromKey( 'not-a-valid-key', 'example.com', 'default' );
	}

	/**
	 *	@covers		::setPrivateKeyFromString
	 */
	public function testShortKeyThrows(): void
	{
		$key	= openssl_pkey_new( ['private_key_bits' => 512, 'private_key_type' => OPENSSL_KEYTYPE_RSA] );
		if( FALSE === $key )
			self::markTestSkipped( 'OpenSSL does not create 512 bit keys' );
		openssl_pkey_export( $key, $pem );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'at least 1024 bits' );
		Dkim::createFromKey( $pem, 'example.com', 'default' );
	}

	/**
	 *	@covers		::setAlgorithm
	 */
	public function testSetAlgorithm(): void
	{
		$signer	= $this->createSigner()->setAlgorithm( 'RSA-SHA256' );
		self::assertStringContainsString( 'a=rsa-sha256;', $signer->sign( $this->getRfcMessage() ) );

		$this->expectException( InvalidArgumentException::class );
		$signer->setAlgorithm( 'rsa-sha1' );
	}

	protected function createSigner( string $headerMode = Canonicalizer::RELAXED, string $bodyMode = Canonicalizer::RELAXED ): Dkim
	{
		return Dkim::createFromKey( $this->rsaKey, 'football.example.com', 'test', $headerMode, $bodyMode )
			->setTimestamp( 1528637909 );
	}

	/**
	 *	Unsigned message of RFC 8463, Appendix A.
	 */
	protected function getRfcMessage(): string
	{
		$message	= (string) file_get_contents( $this->dataPath.'rfc8463-message.eml' );
		return substr( $message, (int) strpos( $message, 'From: ' ) );
	}
}
