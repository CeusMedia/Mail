<?php
/**
 *	Integration test for sending by SMTP, received by Mailpit.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Mailpit
 */

namespace CeusMedia\MailTest\Integration\Mailpit;

use CeusMedia\Mail\Transport\Result;
use InvalidArgumentException;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\SMTP
 */
class SendTest extends MailpitTestCase
{
	/**
	 *	@covers		::send
	 */
	public function testSendDeliversMessage(): void
	{
		$message	= $this->createMessage()->addText( 'Hallo Welt' );
		$results	= $this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );

		self::assertCount( 1, $results );
		self::assertSame( Result::STATUS_SUCCESS, $results[0]->getStatus() );
		self::assertSame( 250, $results[0]->getCode() );
		self::assertSame( 'receiver@example.org', $results[0]->getReceiver()->getAddress() );

		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		self::assertSame( 'sender@example.org', $received['From']['Address'] );
		self::assertSame( ['receiver@example.org'], array_column( $received['To'], 'Address' ) );
		self::assertSame( 'Hallo Welt', rtrim( $received['Text'] ) );
		self::assertSame( 'sender@example.org', $received['ReturnPath'] );
	}

	/**
	 *	EHLO names this client, Mailpit notes it in the Received header.
	 *	@covers		::send
	 *	@covers		::setHeloHost
	 */
	public function testHeloHostIsUsed(): void
	{
		$message	= $this->createMessage()->addText( 'Hallo' );
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->setHeloHost( 'app.client.test' )->send( $message );

		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		$raw		= $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] );
		self::assertMatchesRegularExpression( '/^Received: from app\.client\.test /m', $raw );
	}

	/**
	 *	Every recipient must be in the SMTP envelope (RCPT TO), not only the "To" ones.
	 *	Mailpit lists envelope recipients missing in To/Cc headers as Bcc.
	 *	@covers		::send
	 */
	public function testCcAndBccRecipientsAreDelivered(): void
	{
		$message	= $this->createMessage()
			->addRecipient( 'copy@example.org', NULL, 'cc' )
			->addRecipient( 'hidden@example.org', NULL, 'bcc' )
			->addText( 'Hallo' );
		$results	= $this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );

		$receivers	= array_map( static fn( Result $result ) => $result->getReceiver()->getAddress(), $results );
		self::assertEqualsCanonicalizing( ['receiver@example.org', 'copy@example.org', 'hidden@example.org'], $receivers );

		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		self::assertSame( ['copy@example.org'], array_column( $received['Cc'], 'Address' ) );
		self::assertSame( ['hidden@example.org'], array_column( $received['Bcc'] ?? [], 'Address' ) );
	}

	/**
	 *	Bcc recipients must not be visible to other recipients.
	 *	Mailpit prepends own headers (Bcc, Return-Path, Received), so only the headers after them are checked.
	 *	@covers		::send
	 */
	public function testBccIsNotInHeaders(): void
	{
		$message	= $this->createMessage()
			->addRecipient( 'hidden@example.org', NULL, 'bcc' )
			->addText( 'Hallo' );
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );

		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		$raw		= $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] );
		$headers	= (string) strstr( $raw, "\r\n\r\n", TRUE );
		$headers	= (string) preg_replace( '/\A.*?^Received:.*?\r\n(?![ \t])/ms', '', $headers );
		self::assertStringStartsWith( 'From:', $headers );
		self::assertStringNotContainsStringIgnoringCase( 'hidden@example.org', $headers );
		self::assertDoesNotMatchRegularExpression( '/^Bcc:/mi', $headers );
	}

	/**
	 *	Line breaks in subject must not start a new header (header injection), they become a space.
	 *	@covers		::send
	 */
	public function testSubjectLineBreaksDoNotInjectHeaders(): void
	{
		$message	= $this->createMessage( 'Injection' )->addText( 'Hallo' );
		$subject	= (string) $message->getSubject();
		$message->setSubject( $subject."\r\nBcc: victim@example.org\r\nX-Injected: yes" );
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );

		$received	= $this->fetchMessageBySubject( self::INSTANCE_PLAIN, $subject.' Bcc: victim@example.org X-Injected: yes' );
		self::assertSame( [], $received['Bcc'] );
		$raw		= $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] );
		$headers	= (string) strstr( $raw, "\r\n\r\n", TRUE );
		self::assertDoesNotMatchRegularExpression( '/^X-Injected:/mi', $headers );
	}

	/**
	 *	A custom header with line breaks is rejected before connecting.
	 *	@covers		::send
	 */
	public function testHeaderWithLineBreakIsRejected(): void
	{
		$message	= $this->createMessage()
			->addHeaderPair( 'X-Custom', "value\r\nBcc: victim@example.org" )
			->addText( 'Hallo' );
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Header field "X-Custom" contains line break' );
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );
	}

	/**
	 *	To, Cc and Reply-To are one header each, listing all addresses (RFC 5322, 3.6).
	 *	@covers		::send
	 */
	public function testRecipientHeadersOncePerType(): void
	{
		$message	= $this->createMessage()
			->addRecipient( 'second@example.org', 'Doe, John' )
			->addRecipient( 'copy1@example.org', NULL, 'cc' )
			->addRecipient( 'copy2@example.org', 'Zoë Müller', 'cc' )
			->addReplyTo( 'reply1@example.org' )
			->addReplyTo( 'reply2@example.org', 'Jürgen' )
			->addText( 'Hallo' );
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );

		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		$headers	= (string) strstr( $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] ), "\r\n\r\n", TRUE );
		foreach( ['To', 'Cc', 'Reply-To'] as $name )
			self::assertSame( 1, preg_match_all( '/^'.$name.':/m', $headers ), $name );

		self::assertSame( ['receiver@example.org', 'second@example.org'], array_column( $received['To'], 'Address' ) );
		self::assertSame( 'Doe, John', $received['To'][1]['Name'] );
		self::assertSame( ['copy1@example.org', 'copy2@example.org'], array_column( $received['Cc'], 'Address' ) );
		self::assertSame( 'Zoë Müller', $received['Cc'][1]['Name'] );
		self::assertSame( ['reply1@example.org', 'reply2@example.org'], array_column( $received['ReplyTo'], 'Address' ) );
		self::assertSame( 'Jürgen', $received['ReplyTo'][1]['Name'] );
	}

	/**
	 *	Several messages over separate connections of the same transport configuration.
	 *	@covers		::send
	 */
	public function testSendSeveralMessages(): void
	{
		$messages	= [];
		for( $i = 1; $i <= 3; $i++ ){
			$messages[$i]	= $this->createMessage( 'Nachricht '.$i )->addText( 'Nummer '.$i );
			$this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $messages[$i] );
		}
		foreach( $messages as $i => $message )
			self::assertSame( 'Nummer '.$i, rtrim( $this->fetchMessage( self::INSTANCE_PLAIN, $message )['Text'] ) );
	}
}
