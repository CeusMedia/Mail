<?php
namespace CeusMedia\MailTest\Transport\SMTP\Auth;

use CeusMedia\Mail\Transport\SMTP\Auth\Login;
use CeusMedia\Mail\Transport\SMTP\Auth\Plain;
use CeusMedia\Mail\Transport\SMTP\Auth\Xoauth2;
use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\MailTest\TestCase;

use RuntimeException;

class AuthStrategyTest extends TestCase
{
	//  --  AUTH LOGIN  --  //

	public function testLoginGetMechanism(): void
	{
		$auth	= new Login( 'user', 'pass' );
		$this->assertSame( 'LOGIN', $auth->getMechanism() );
	}

	public function testLoginAuthenticateSuccess(): void
	{
		$auth	= new Login( 'user', 'pass' );

		$responses	= [
			new Response( 334, 'Username:'),
			new Response( 334, 'Password:'),
			new Response( 235, '2.0.0 OK Authenticated'),
		];
		$connection	= new SmtpConnectionMock( $responses );

		$auth->authenticate( $connection );

		$this->assertCount( 3, $connection->sentCommands );
		$this->assertSame( 'AUTH LOGIN', $connection->sentCommands[0] );
		//  Username and password are base64-encoded
		$this->assertNotEmpty( $connection->sentCommands[1] );
		$this->assertNotEmpty( $connection->sentCommands[2] );
		//  Credentials are masked in verbose output
		$this->assertNull( $connection->verboseMessages[0] );
		$this->assertSame( '[username hidden]', $connection->verboseMessages[1] );
		$this->assertSame( '[password hidden]', $connection->verboseMessages[2] );
	}

	public function testLoginAuthenticateFailure(): void
	{
		$auth	= new Login( 'user', 'wrong' );

		$responses	= [
			new Response( 334, 'Username:'),
			new Response( 334, 'Password:'),
			new Response( 535, '5.7.8 Authentication failed'),
		];
		$connection	= new SmtpConnectionMock( $responses );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'AUTH LOGIN failed' );
		$auth->authenticate( $connection );
	}

	//  --  AUTH PLAIN  --  //

	public function testPlainGetMechanism(): void
	{
		$auth	= new Plain( 'user', 'pass' );
		$this->assertSame( 'PLAIN', $auth->getMechanism() );
	}

	public function testPlainAuthenticateSuccess(): void
	{
		$auth	= new Plain( 'user', 'pass' );

		$connection	= new SmtpConnectionMock( [
			new Response( 235, '2.0.0 OK Authenticated'),
		] );

		$auth->authenticate( $connection );

		$this->assertCount( 1, $connection->sentCommands );
		$this->assertStringStartsWith( 'AUTH PLAIN ', $connection->sentCommands[0] );
	}

	public function testPlainAuthenticateFailure(): void
	{
		$auth	= new Plain( 'user', 'wrong' );

		$connection	= new SmtpConnectionMock( [
			new Response( 535, '5.7.8 Authentication failed'),
		] );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'AUTH PLAIN failed' );
		$auth->authenticate( $connection );
	}

	public function testPlainWithAuthzId(): void
	{
		$auth	= new Plain( 'auth_id', 'pass', 'authz_id' );

		$connection	= new SmtpConnectionMock( [
			new Response( 235, '2.0.0 OK Authenticated'),
		] );

		$auth->authenticate( $connection );

		//  Extract the base64 payload
		$parts	= explode( ' ', $connection->sentCommands[0], 2 );
		$decoded	= base64_decode( $parts[1] );
		$this->assertSame( "authz_id\0auth_id\0pass", $decoded );
	}

	//  --  AUTH XOAUTH2  --  //

	public function testXoauth2GetMechanism(): void
	{
		$auth	= new Xoauth2( 'user@example.com', 'ya29.test_token' );
		$this->assertSame( 'XOAUTH2', $auth->getMechanism() );
	}

	public function testXoauth2AuthenticateSuccessSaslIR(): void
	{
		$auth	= new Xoauth2( 'user@example.com', 'ya29.test_token' );

		$connection	= new SmtpConnectionMock( [
			new Response( 235, '2.0.0 OK Authenticated'),
		] );

		$auth->authenticate( $connection );

		$this->assertCount( 1, $connection->sentCommands );
		$this->assertStringStartsWith( 'AUTH XOAUTH2 ', $connection->sentCommands[0] );
	}

	public function testXoauth2AuthenticateFailure(): void
	{
		$auth	= new Xoauth2( 'user@example.com', 'invalid_token' );

		$connection	= new SmtpConnectionMock( [
			new Response( 535, '5.7.8 Invalid credentials'),
		] );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'AUTH XOAUTH2 failed' );
		$auth->authenticate( $connection );
	}

	public function testXoauth2AuthenticateFallbackChallengeResponse(): void
	{
		$auth	= new Xoauth2( 'user@example.com', 'ya29.test_token' );

		$responses	= [
			new Response( 334, ' '),  //  Server doesn't support SASL IR
			new Response( 235, '2.0.0 OK Authenticated'),
		];
		$connection	= new SmtpConnectionMock( $responses );

		$auth->authenticate( $connection );

		//  SASL IR failed, sent empty response, then got success
		$this->assertCount( 2, $connection->sentCommands );
		$this->assertStringStartsWith( 'AUTH XOAUTH2 ', $connection->sentCommands[0] );
		$this->assertSame( '', $connection->sentCommands[1] );
	}
}

