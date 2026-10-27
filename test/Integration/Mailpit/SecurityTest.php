<?php
/**
 *	Integration test for encrypted and authenticated sending by SMTP, against Mailpit.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Mailpit
 */

namespace CeusMedia\MailTest\Integration\Mailpit;

use CeusMedia\Mail\Transport\Result;
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;
use CeusMedia\Mail\Transport\SMTP\Response;
use RuntimeException;

/**
 *	Instance "starttls" requires STARTTLS, instance "tls" requires implicit TLS,
 *	both with a self-signed certificate for "localhost" and login tester/secret.
 *
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\SMTP
 */
class SecurityTest extends MailpitTestCase
{
	public static function secureInstanceProvider(): array
	{
		return [
			'STARTTLS'		=> [self::INSTANCE_STARTTLS],
			'implicit TLS'	=> [self::INSTANCE_TLS],
		];
	}

	/**
	 *	Encrypted connection and login: on STARTTLS the client must greet again after the handshake (RFC 3207, 4.2).
	 *	@dataProvider	secureInstanceProvider
	 *	@covers			::send
	 *	@covers			::setSecure
	 *	@covers			::setImplicitTls
	 *	@covers			::setAuth
	 */
	public function testSendEncryptedWithLogin( string $instance ): void
	{
		$message	= $this->createMessage()->addText( 'Verschlüsselt' );
		$results	= $this->createTransport( $instance )->send( $message );

		self::assertSame( Result::STATUS_SUCCESS, $results[0]->getStatus() );
		$received	= $this->fetchMessage( $instance, $message );
		self::assertSame( self::USERNAME, $received['Username'] );
		self::assertSame( 'Verschlüsselt', rtrim( $received['Text'] ) );
	}

	/**
	 *	The certificate is validated by default: a self-signed one, not trusted, is rejected with reason.
	 *	@covers		::send
	 */
	public function testUntrustedCertificateIsRejectedOnStartTls(): void
	{
		$transport	= $this->createTransport( self::INSTANCE_STARTTLS, TRUE, FALSE );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Crypto negotiation failed: ' );
		$this->expectExceptionMessage( 'certificate verify failed' );
		$transport->send( $this->createMessage()->addText( 'Hallo' ) );
	}

	/**
	 *	@covers		::send
	 */
	public function testUntrustedCertificateIsRejectedOnImplicitTls(): void
	{
		$transport	= $this->createTransport( self::INSTANCE_TLS, TRUE, FALSE );
		try{
			$transport->send( $this->createMessage()->addText( 'Hallo' ) );
			self::fail( 'Untrusted certificate accepted' );
		}
		catch( SmtpException $e ){
			self::assertSame( Response::ERROR_CRYPTO_FAILED, $e->getCode() );
			self::assertStringContainsString( 'certificate verify failed', $e->getMessage() );
		}
	}

	/**
	 *	Certificate validation can be switched off, e.g. for self-signed certificates in development.
	 *	@dataProvider	secureInstanceProvider
	 *	@covers			::setSecure
	 *	@covers			::send
	 */
	public function testSendWithoutCertificateValidation( string $instance ): void
	{
		$message	= $this->createMessage()->addText( 'Ungeprüft' );
		$transport	= $this->createTransport( $instance, TRUE, FALSE )->setSecure( TRUE, FALSE );
		$results	= $transport->send( $message );

		self::assertSame( Result::STATUS_SUCCESS, $results[0]->getStatus() );
		self::assertSame( 'Ungeprüft', rtrim( $this->fetchMessage( $instance, $message )['Text'] ) );
	}

	/**
	 *	Options set explicitly win over the validation flag.
	 *	@covers		::setSslOptions
	 *	@covers		::send
	 */
	public function testSslOptionsOverrideValidationFlag(): void
	{
		$transport	= $this->createTransport( self::INSTANCE_STARTTLS, TRUE, FALSE )
			->setSecure( TRUE, FALSE )
			->setSslOptions( ['verify_peer' => TRUE, 'allow_self_signed' => FALSE] );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'certificate verify failed' );
		$transport->send( $this->createMessage()->addText( 'Hallo' ) );
	}

	/**
	 *	@dataProvider	secureInstanceProvider
	 *	@covers			::send
	 */
	public function testWrongPasswordIsRejected( string $instance ): void
	{
		$transport	= $this->createTransport( $instance )->setPassword( 'wrong' );
		try{
			$transport->send( $this->createMessage()->addText( 'Hallo' ) );
			self::fail( 'Login with wrong password succeeded' );
		}
		catch( SmtpException $e ){
			self::assertSame( Response::ERROR_LOGIN_FAILED, $e->getCode() );
			self::assertSame( 535, $e->getResponse()?->getCode() );
		}
	}

	/**
	 *	A server requiring login rejects the sender without it.
	 *	@covers		::send
	 */
	public function testSendWithoutLoginIsRejected(): void
	{
		$transport	= $this->createTransport( self::INSTANCE_STARTTLS, FALSE );
		try{
			$transport->send( $this->createMessage()->addText( 'Hallo' ) );
			self::fail( 'Sending without login succeeded' );
		}
		catch( SmtpException $e ){
			self::assertSame( 530, $e->getResponse()?->getCode() );
		}
	}

	/**
	 *	A server requiring STARTTLS rejects login over the unencrypted connection.
	 *	@covers		::send
	 */
	public function testUnencryptedLoginIsRejected(): void
	{
		$transport	= $this->createTransport( self::INSTANCE_STARTTLS )->setSecure( FALSE );
		try{
			$transport->send( $this->createMessage()->addText( 'Hallo' ) );
			self::fail( 'Unencrypted login succeeded' );
		}
		catch( SmtpException $e ){
			self::assertSame( 530, $e->getResponse()?->getCode() );
			self::assertStringContainsString( 'STARTTLS', $e->getMessage() );
		}
	}

	/**
	 *	Implicit TLS port, but client waits for an unencrypted greeting: fails after read timeout, with hint.
	 *	@covers		::setReadTimeout
	 *	@covers		::send
	 */
	public function testStartTlsOnImplicitTlsPortFails(): void
	{
		$transport	= $this->createTransport( self::INSTANCE_TLS )->setImplicitTls( FALSE )->setReadTimeout( 1 );
		$start		= microtime( TRUE );
		try{
			$transport->send( $this->createMessage()->addText( 'Hallo' ) );
			self::fail( 'Unencrypted connection to implicit TLS port succeeded' );
		}
		catch( SmtpException $e ){
			self::assertSame( Response::ERROR_CONNECTION_FAILED, $e->getCode() );
			self::assertStringContainsString( 'No response from SMTP server within 1 seconds', $e->getMessage() );
			self::assertStringContainsString( 'implicit TLS', $e->getMessage() );
		}
		self::assertLessThan( 10, microtime( TRUE ) - $start, 'read timeout of socket applied' );
	}

	/**
	 *	Verbose mode shows the dialog with a real server, but never the credentials.
	 *	@dataProvider	secureInstanceProvider
	 *	@covers			::send
	 *	@covers			::sendChunk
	 */
	public function testVerboseModeHidesCredentials( string $instance ): void
	{
		$transport	= $this->createTransport( $instance )->setVerbose( TRUE );
		ob_start();
		try{
			$transport->send( $this->createMessage()->addText( 'Hallo' ) );
		}
		finally{
			$output	= (string) ob_get_clean();
		}

		self::assertStringContainsString( 'AUTH LOGIN', $output );
		self::assertStringContainsString( '235', $output );
		foreach( [self::PASSWORD, base64_encode( self::PASSWORD ), base64_encode( self::USERNAME )] as $secret )
			self::assertStringNotContainsString( $secret, $output );
	}
}
