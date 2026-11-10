<?php
/**
 *	Integration test for DKIM signed messages sent by SMTP, received by Mailpit.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Mailpit
 */

namespace CeusMedia\MailTest\Integration\Mailpit;

use CeusMedia\Mail\Message\Signer\Canonicalizer;
use CeusMedia\Mail\Message\Signer\Dkim;
use CeusMedia\MailTest\Message\Signer\DkimVerifier;

/**
 *	Mailpit does not verify DKIM, so the received source is verified by the test verifier.
 *	Mailpit prepends own headers (Return-Path, Received), which are not signed and do not break the signature.
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\SMTP
 */
class DkimTest extends MailpitTestCase
{
	/**
	 *	@return		array<string,array{string,string}>
	 */
	public static function provideModes(): array
	{
		return [
			'relaxed/relaxed'	=> [Canonicalizer::RELAXED, Canonicalizer::RELAXED],
			'simple/simple'		=> [Canonicalizer::SIMPLE, Canonicalizer::SIMPLE],
		];
	}

	/**
	 *	Signature of a message with text, HTML and attachment is valid as received.
	 *	@dataProvider	provideModes
	 *	@covers		::send
	 *	@covers		::setDkimSigner
	 */
	public function testSignedMessageIsVerifiable( string $headerMode, string $bodyMode ): void
	{
		$signer		= $this->createSigner( $headerMode, $bodyMode );
		$message	= $this->createMessage( 'DKIM Grüße '.$headerMode.'/'.$bodyMode )
			->addRecipient( 'copy@example.org', 'Kopie Empfänger', 'cc' )
			->addText( "Hallo,\r\n\r\nZeile mit  Leerzeichen   \r\n.Punkt am Anfang\r\n", 'UTF-8', 'quoted-printable' )
			->addHTML( '<p>Hallo <b>Welt</b></p>' )
			->addAttachment( __FILE__, 'text/plain' );
		$this->createTransport( self::INSTANCE_STARTTLS )->setDkimSigner( $signer )->send( $message );

		$received	= $this->fetchMessage( self::INSTANCE_STARTTLS, $message );
		$raw		= $this->fetchRaw( self::INSTANCE_STARTTLS, $received['ID'] );
		$result		= DkimVerifier::verify( $raw, $this->getPublicKey( $signer ) );
		self::assertTrue( $result['bodyHash'], 'Body hash does not match' );
		self::assertTrue( $result['signature'], 'Signature is not valid' );
		self::assertSame( $headerMode.'/'.$bodyMode, $result['tags']['c'] );
	}

	/**
	 *	A change after signing is detected, as received.
	 *	@covers		::send
	 */
	public function testChangedMessageIsDetected(): void
	{
		$signer		= $this->createSigner();
		$message	= $this->createMessage( 'DKIM changed' )->addText( 'Hallo Welt' );
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->setDkimSigner( $signer )->send( $message );

		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		$raw		= $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] );
		$subject	= (string) $message->getSubject();
		$result		= DkimVerifier::verify( str_replace( $subject, $subject.'!', $raw ), $this->getPublicKey( $signer ) );
		self::assertTrue( $result['bodyHash'] );
		self::assertFalse( $result['signature'] );
	}

	protected function createSigner( string $headerMode = Canonicalizer::RELAXED, string $bodyMode = Canonicalizer::RELAXED ): Dkim
	{
		return new Dkim( $this->pathLibrary.'test/data/dkim/rfc8463-rsa.pem', 'example.org', 'test', $headerMode, $bodyMode );
	}

	protected function getPublicKey( Dkim $signer ): string
	{
		return substr( $signer->getDnsRecord(), strlen( 'v=DKIM1; k=rsa; p=' ) );
	}
}
