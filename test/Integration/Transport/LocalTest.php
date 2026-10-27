<?php
/**
 *	Integration test for sending by PHP's mail function, against a fake sendmail.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Transport
 */

namespace CeusMedia\MailTest\Integration\Transport;

use CeusMedia\Mail\Transport\Result;
use CeusMedia\MailTest\TestCase;
use RuntimeException;

/**
 *	Each test sends in an own PHP process, since sendmail_path can only be set on startup.
 *	The fake sendmail (Local/sendmail.php) records what mail() hands over, for sendmail -t.
 *
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\Local
 */
class LocalTest extends TestCase
{
	private string $captureFile;

	protected function setUp(): void
	{
		$this->captureFile	= (string) tempnam( sys_get_temp_dir(), 'ceusmedia-mail-sendmail-' );
	}

	protected function tearDown(): void
	{
		@unlink( $this->captureFile );
	}

	/**
	 *	One message for all recipients: sendmail -t reads To, Cc and Bcc from headers and removes Bcc.
	 *	mail() adds To and Subject itself, so they must not be in the additional headers again.
	 *	@covers		::send
	 */
	public function testHandsOverOneMessageForAllRecipients(): void
	{
		$results	= $this->send( PHP_BINARY.' '.__DIR__.'/Local/sendmail.php -t -i', 'Grüße aus Köln' );

		self::assertSame( ['to1@example.org', 'to2@example.org', 'copy@example.org', 'hidden@example.org'], array_column( $results, 'receiver' ) );
		self::assertSame( [Result::STATUS_SUCCESS], array_unique( array_column( $results, 'status' ) ) );

		$calls	= $this->getSendmailCalls();
		self::assertCount( 1, $calls );
		[$headers, $body]	= explode( "\r\n\r\n", $calls[0]['message'], 2 );

		self::assertSame( 1, preg_match_all( '/^To:/mi', $headers ) );
		self::assertSame( 1, preg_match_all( '/^Subject:/mi', $headers ) );
		self::assertMatchesRegularExpression( '/^To: to1@example\.org, =\?UTF-8\?.+\?= <to2@example\.org>\r?$/m', $headers );
		self::assertMatchesRegularExpression( '/^Cc: copy@example\.org\r?$/m', $headers );
		self::assertMatchesRegularExpression( '/^Bcc: hidden@example\.org\r?$/m', $headers );
		self::assertSame( 1, preg_match_all( '/^Bcc:/mi', $headers ), 'Bcc of message headers not doubled' );
		self::assertMatchesRegularExpression( '/^Subject: =\?UTF-8\?/m', $headers );

		self::assertStringNotContainsString( 'From:', $body, 'body without headers' );
		self::assertSame( "Grüße\r\n.mit Punkt", base64_decode( $body, TRUE ) );
	}

	/**
	 *	A failing sendmail is reported for all recipients.
	 *	The fake reads the message before failing, else mail() would not notice.
	 *	@covers		::send
	 */
	public function testFailureIsReportedForAllRecipients(): void
	{
		$results	= $this->send( "sh -c 'cat >/dev/null; exit 1'", 'Test' );

		self::assertCount( 4, $results );
		self::assertSame( [Result::STATUS_ERROR], array_unique( array_column( $results, 'status' ) ) );
	}

	/**
	 *	Line break in subject is replaced by a space on rendering, like by SMTP: sent, no injected header.
	 *	@covers		::send
	 */
	public function testLineBreakInSubjectIsNoInjection(): void
	{
		$results	= $this->send( PHP_BINARY.' '.__DIR__.'/Local/sendmail.php -t -i', "Hallo\r\nBcc: evil@example.org" );

		self::assertSame( [Result::STATUS_SUCCESS], array_unique( array_column( $results, 'status' ) ) );
		[$headers]	= explode( "\r\n\r\n", $this->getSendmailCalls()[0]['message'], 2 );
		self::assertMatchesRegularExpression( '/^Subject: Hallo Bcc: evil@example\.org\r?$/m', $headers );
		self::assertDoesNotMatchRegularExpression( '/^Bcc: evil/m', $headers );
	}

	/**
	 *	Message with Bcc recipients only: To is set to an empty group, Bcc header for sendmail -t.
	 *	@covers		::send
	 */
	public function testBccOnlyMessage(): void
	{
		$results	= $this->send( PHP_BINARY.' '.__DIR__.'/Local/sendmail.php -t -i', 'Test', 'bcc-only' );

		self::assertSame( ['hidden@example.org'], array_column( $results, 'receiver' ) );
		self::assertSame( [Result::STATUS_SUCCESS], array_column( $results, 'status' ) );
		[$headers]	= explode( "\r\n\r\n", $this->getSendmailCalls()[0]['message'], 2 );
		self::assertMatchesRegularExpression( '/^To: undisclosed-recipients:;\r?$/m', $headers );
		self::assertMatchesRegularExpression( '/^Bcc: hidden@example\.org\r?$/m', $headers );
	}

	//  --  PRIVATE  --  //

	/**
	 *	@return		array<int,array{arguments:string,message:string}>
	 */
	private function getSendmailCalls(): array
	{
		$calls	= [];
		foreach( array_slice( explode( "=== CALL ===\n", (string) file_get_contents( $this->captureFile ) ), 1 ) as $call ){
			[$arguments, $message]	= explode( "\n", $call, 2 );
			$calls[]	= ['arguments' => $arguments, 'message' => $message];
		}
		return $calls;
	}

	/**
	 *	Sends test message (Local/send.php) in own PHP process.
	 *	@return		array<int,array{receiver:string,status:string,message:string}>
	 */
	private function send( string $sendmailPath, string $subject, string $variant = '' ): array
	{
		$command	= [PHP_BINARY, '-d', 'sendmail_path='.$sendmailPath, __DIR__.'/Local/send.php', $subject, $variant];
		$env		= ['SENDMAIL_CAPTURE' => $this->captureFile] + getenv();
		$process	= proc_open( $command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, NULL, $env );
		if( FALSE === $process )
			throw new RuntimeException( 'Starting PHP process failed' );
		$output		= (string) stream_get_contents( $pipes[1] );
		$errors		= (string) stream_get_contents( $pipes[2] );
		proc_close( $process );
		$results	= json_decode( $output, TRUE );
		if( !is_array( $results ) )
			self::fail( 'Sending failed: '.$output.$errors );
		return $results;
	}
}
