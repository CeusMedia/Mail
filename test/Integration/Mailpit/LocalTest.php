<?php
/**
 *	Integration test for sending by PHP's mail function, received by Mailpit.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Mailpit
 */

namespace CeusMedia\MailTest\Integration\Mailpit;

use CeusMedia\Mail\Transport\Result;
use RuntimeException;

/**
 *	Sends by Mailpit's sendmail replacement inside the container of instance "plain".
 *	Sending runs in own PHP process (test/Integration/Transport/Local/send.php), since sendmail_path can only be set on startup.
 *
 *	Note: Mailpit's sendmail reads recipients from headers, but ignores the other semantics of sendmail -t.
 *	What mail() hands over is tested in \CeusMedia\MailTest\Integration\Transport\LocalTest.
 *
 *	Environment: MAILPIT_SENDMAIL (default: docker exec into container ceusmedia-mail-mailpit-plain)
 *
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\Local
 */
class LocalTest extends MailpitTestCase
{
	private const CONTAINER		= 'ceusmedia-mail-mailpit-plain';

	/**
	 *	@covers		::send
	 */
	public function testSendDeliversToAllRecipients(): void
	{
		$subject	= 'Grüße über mail() ['.uniqid( 'mp', TRUE ).']';
		$results	= $this->send( $subject );
		self::assertSame( [Result::STATUS_SUCCESS], array_unique( array_column( $results, 'status' ) ) );

		$received	= $this->fetchMessageBySubject( self::INSTANCE_PLAIN, $subject );
		self::assertSame( 'Jürgen Müller', $received['From']['Name'] );
		self::assertSame( ['to1@example.org', 'to2@example.org'], array_column( $received['To'], 'Address' ) );
		self::assertSame( 'Zoë Empfänger', $received['To'][1]['Name'] );
		self::assertSame( ['copy@example.org'], array_column( $received['Cc'], 'Address' ) );
		self::assertSame( ['hidden@example.org'], array_column( $received['Bcc'] ?? [], 'Address' ) );
		self::assertSame( "Grüße\n.mit Punkt", rtrim( str_replace( "\r\n", "\n", $received['Text'] ) ) );

		self::assertSame( 1, $this->fetchMessageCount( $subject ), 'one message for all recipients' );
	}

	//  --  PRIVATE  --  //

	private function fetchMessageCount( string $subject ): int
	{
		$result	= $this->requestApi( self::INSTANCE_PLAIN, '/api/v1/search?query='.rawurlencode( 'subject:"'.$subject.'"' ) );
		return count( $result['messages'] );
	}

	private function getSendmailPath(): string
	{
		$path	= getenv( 'MAILPIT_SENDMAIL' );
		if( FALSE !== $path && '' !== $path )
			return $path;
		exec( 'docker inspect --format "{{.State.Running}}" '.self::CONTAINER.' 2>/dev/null', $output, $code );
		if( 0 !== $code || 'true' !== trim( $output[0] ?? '' ) )
			self::markTestSkipped( 'Container '.self::CONTAINER.' not running, start with: util/mailpit/mailpit.sh up' );
		return 'docker exec -i '.self::CONTAINER.' /mailpit sendmail -t -i';
	}

	/**
	 *	@return		array<int,array{receiver:string,status:string,message:string}>
	 */
	private function send( string $subject ): array
	{
		$this->requireInstance( self::INSTANCE_PLAIN );
		$script		= $this->pathTests.'Integration/Transport/Local/send.php';
		$command	= [PHP_BINARY, '-d', 'sendmail_path='.$this->getSendmailPath(), $script, $subject];
		$process	= proc_open( $command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes );
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
