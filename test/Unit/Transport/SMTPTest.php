<?php
/**
 *	Unit test for SMTP transport, against a fake server socket.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Transport
 */

namespace CeusMedia\MailTest\Unit\Transport;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\Result;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;
use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\Mail\Transport\SMTP\Socket;
use CeusMedia\MailTest\TestCase;
use InvalidArgumentException;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\SMTP
 */
class SMTPTest extends TestCase
{
	/**
	 *	After STARTTLS the client must send EHLO again before AUTH (RFC 3207, 4.2).
	 *	@covers		::send
	 */
	public function testGreetsAgainAfterStartTls(): void
	{
		$socket	= new FakeServerSocket();
		$this->createTransport( $socket, 587 )->send( $this->createMessage() );

		$commands	= $socket->getCommands();
		self::assertSame( ['EHLO', 'STARTTLS', 'EHLO', 'AUTH', '***', '***', 'MAIL', 'RCPT', 'DATA'], array_slice( $commands, 0, 9 ) );
		self::assertTrue( $socket->cryptoEnabled );
	}

	/**
	 *	Without TLS there is only one greeting.
	 *	@covers		::send
	 */
	public function testGreetsOnceWithoutTls(): void
	{
		$socket	= new FakeServerSocket();
		$this->createTransport( $socket, 25 )->send( $this->createMessage() );
		self::assertSame( ['EHLO', 'AUTH', '***', '***', 'MAIL'], array_slice( $socket->getCommands(), 0, 5 ) );
		self::assertFalse( $socket->cryptoEnabled );
	}

	/**
	 *	Port 465 uses implicit TLS (handshake before the greeting), so no STARTTLS.
	 *	@covers		::setPort
	 *	@covers		::send
	 */
	public function testImplicitTlsOnPort465(): void
	{
		$socket	= new FakeServerSocket();
		$this->createTransport( $socket, 465 )->send( $this->createMessage() );
		self::assertTrue( $socket->isImplicitTlsRequested() );
		self::assertSame( ['EHLO', 'AUTH', '***', '***', 'MAIL'], array_slice( $socket->getCommands(), 0, 5 ) );

		$socket	= new FakeServerSocket();
		$this->createTransport( $socket, 587 )->send( $this->createMessage() );
		self::assertFalse( $socket->isImplicitTlsRequested(), 'STARTTLS on port 587' );

		$socket	= new FakeServerSocket();
		$this->createTransport( $socket, 465 )->setSecure( FALSE )->send( $this->createMessage() );
		self::assertFalse( $socket->isImplicitTlsRequested(), 'encryption switched off' );
	}

	/**
	 *	EHLO names this client, not the server (RFC 5321, 4.1.1.1).
	 *	@covers		::setHeloHost
	 *	@covers		::getHeloHost
	 */
	public function testHeloHost(): void
	{
		$socket	= new FakeServerSocket();
		$this->createTransport( $socket, 587 )->setHeloHost( 'app.example.org' )->send( $this->createMessage() );
		self::assertSame( ['EHLO app.example.org', 'STARTTLS', 'EHLO app.example.org'], array_slice( $socket->getLines(), 0, 3 ) );

		//  detected: fully qualified host name, else local address as address literal
		$socket	= new FakeServerSocket( localAddress: '192.0.2.7:54321' );
		$this->createTransport( $socket, 25 )->send( $this->createMessage() );
		$hostname	= (string) gethostname();
		$expected	= str_contains( $hostname, '.' ) ? $hostname : '[192.0.2.7]';
		self::assertSame( 'EHLO '.$expected, $socket->getLines()[0] );

		$socket	= new FakeServerSocket( localAddress: '[2001:db8::7]:54321' );
		$this->createTransport( $socket, 25 )->send( $this->createMessage() );
		$expected	= str_contains( $hostname, '.' ) ? $hostname : '[IPv6:2001:db8::7]';
		self::assertSame( 'EHLO '.$expected, $socket->getLines()[0] );
	}

	/**
	 *	A strict server rejects AUTH without a new greeting after TLS; the transport passes now.
	 *	@covers		::send
	 */
	public function testStrictServerAcceptsLogin(): void
	{
		$socket	= new FakeServerSocket( TRUE );
		$results	= $this->createTransport( $socket, 587 )->send( $this->createMessage() );
		self::assertSame( Result::STATUS_SUCCESS, $results[0]->getStatus() );
	}

	/**
	 *	Verbose mode shows the SMTP dialog, but never the credentials.
	 *	@covers		::send
	 *	@covers		::sendChunk
	 */
	public function testVerboseModeHidesCredentials(): void
	{
		$transport	= $this->createTransport( new FakeServerSocket(), 587 )->setVerbose( TRUE );
		ob_start();
		$transport->send( $this->createMessage() );
		$output	= (string) ob_get_clean();

		self::assertStringContainsString( 'AUTH LOGIN', $output );
		self::assertStringContainsString( '[password hidden]', $output );
		foreach( ['geheim123', base64_encode( 'geheim123' ), base64_encode( 'user@example.org' )] as $secret )
			self::assertStringNotContainsString( $secret, $output );
	}

	/**
	 *	A rejected login is reported as SMTP exception.
	 *	@covers		::send
	 */
	public function testFailedLoginThrows(): void
	{
		$socket	= new FakeServerSocket( FALSE, '535 5.7.139 Authentication unsuccessful' );
		$this->expectException( SmtpException::class );
		$this->expectExceptionMessage( '5.7.139' );
		$this->createTransport( $socket, 587 )->send( $this->createMessage() );
	}

	/**
	 *	The envelope gets all recipients, also CC and BCC, each address once.
	 *	@covers		::send
	 */
	public function testAllRecipientsInEnvelope(): void
	{
		$message	= $this->createMessage()
			->addRecipient( 'kopie@example.org', NULL, 'cc' )
			->addRecipient( 'geheim@example.org', NULL, 'bcc' )
			->addRecipient( 'Empfaenger@example.org', NULL, 'bcc' );
		$socket		= new FakeServerSocket();
		$results	= $this->createTransport( $socket, 25 )->send( $message );

		$recipients	= array_values( array_filter( $socket->getLines(), static fn( string $line ) => str_starts_with( $line, 'RCPT' ) ) );
		self::assertSame( [
			'RCPT TO: <empfaenger@example.org>',
			'RCPT TO: <kopie@example.org>',
			'RCPT TO: <geheim@example.org>',
		], $recipients );
		self::assertCount( 3, $results );
	}

	/**
	 *	Message with Bcc recipients only (no To) is sent, without To header (RFC 5322, 3.6.3).
	 *	Without any recipient, sending is refused.
	 *	@covers		::send
	 */
	public function testBccOnlyMessage(): void
	{
		$message	= Message::getInstance()
			->setSender( 'user@example.org' )
			->addRecipient( 'geheim@example.org', NULL, 'bcc' )
			->setSubject( 'Test' )
			->addText( 'Hallo' );
		$socket		= new FakeServerSocket();
		$results	= $this->createTransport( $socket, 25 )->send( $message );

		self::assertSame( ['RCPT TO: <geheim@example.org>'], array_values( array_filter( $socket->getLines(), static fn( string $line ) => str_starts_with( $line, 'RCPT' ) ) ) );
		self::assertSame( Result::STATUS_SUCCESS, $results[0]->getStatus() );
		self::assertDoesNotMatchRegularExpression( '/^(To|Bcc):/mi', $socket->data );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'No mail receiver(s) set' );
		$this->createTransport( new FakeServerSocket(), 25 )->send( Message::getInstance()
			->setSender( 'user@example.org' )->setSubject( 'Test' )->addText( 'Hallo' ) );
	}

	/**
	 *	A line with a single dot ends the data, so leading dots are doubled (RFC 5321, 4.5.2).
	 *	@covers		::send
	 */
	public function testLeadingDotsAreDoubled(): void
	{
		$message	= Message::getInstance()
			->setSender( 'user@example.org' )
			->addRecipient( 'empfaenger@example.org' )
			->setSubject( 'Test' )
			->addText( "Erste\r\n.zweite\r\n.\r\nletzte", 'UTF-8', '8bit' );
		$socket		= new FakeServerSocket();
		$this->createTransport( $socket, 25 )->send( $message );

		self::assertStringContainsString( "\r\nErste\r\n..zweite\r\n..\r\nletzte", $socket->data );
		self::assertDoesNotMatchRegularExpression( '/^\.(?!\.)/m', $socket->data );
	}

	/**
	 *	The read timeout is handed to the socket, invalid values are rejected.
	 *	@covers		::setReadTimeout
	 *	@covers		::send
	 */
	public function testReadTimeout(): void
	{
		$socket	= new FakeServerSocket();
		self::assertSame( 30, $socket->getReadTimeout() );
		$this->createTransport( $socket, 25 )->setReadTimeout( 7 )->send( $this->createMessage() );
		self::assertSame( 7, $socket->getReadTimeout() );

		$this->expectException( InvalidArgumentException::class );
		$this->createTransport( new FakeServerSocket(), 25 )->setReadTimeout( 0 );
	}

	/**
	 *	SSL options of a socket given by setSocket() are kept, own options win.
	 *	@covers		::send
	 *	@covers		::setSslOptions
	 */
	public function testSslOptionsOfGivenSocketAreKept(): void
	{
		$socket	= new FakeServerSocket();
		$socket->setSslOptions( ['cafile' => '/path/ca.pem', 'peer_name' => 'socket.example.org'] );
		$this->createTransport( $socket, 25 )->setSslOptions( ['peer_name' => 'smtp.example.org'] )->send( $this->createMessage() );
		self::assertSame( ['cafile' => '/path/ca.pem', 'peer_name' => 'smtp.example.org'], $socket->getSslOptions() );
	}

	/**
	 *	Switching certificate validation on again removes options of an earlier send.
	 *	@covers		::send
	 *	@covers		::setSecure
	 */
	public function testSslOptionsCanBeUndone(): void
	{
		$socket		= new FakeServerSocket();
		$socket->setSslOptions( ['cafile' => '/path/ca.pem'] );
		$transport	= $this->createTransport( $socket, 25 )->setSecure( FALSE, FALSE );
		$transport->send( $this->createMessage() );
		self::assertFalse( $socket->getSslOptions()['verify_peer'] ?? TRUE );

		$transport->setSecure( FALSE )->send( $this->createMessage() );
		self::assertSame( ['cafile' => '/path/ca.pem'], $socket->getSslOptions() );
	}

	/**
	 *	A rejected recipient is reported, the others still get the message; 251 (will forward) is accepted.
	 *	@covers		::send
	 */
	public function testRejectedRecipientDoesNotStopOthers(): void
	{
		$socket		= new FakeServerSocket();
		$socket->rcptAnswers	= [
			'geheim@example.org'	=> '550 5.1.1 User unknown',
			'kopie@example.org'		=> '251 2.1.5 User not local; will forward',
		];
		$message	= $this->createMessage()
			->addRecipient( 'kopie@example.org', NULL, 'cc' )
			->addRecipient( 'geheim@example.org', NULL, 'bcc' );
		$results	= $this->createTransport( $socket, 25 )->send( $message );

		$statuses	= [];
		foreach( $results as $result )
			$statuses[$result->getReceiver()->getAddress()]	= [$result->getStatus(), $result->getCode()];
		self::assertSame( [
			'empfaenger@example.org'	=> [Result::STATUS_SUCCESS, 250],
			'kopie@example.org'			=> [Result::STATUS_SUCCESS, 250],
			'geheim@example.org'		=> [Result::STATUS_ERROR, 550],
		], $statuses );
		self::assertContains( 'DATA', $socket->getCommands() );
	}

	/**
	 *	No recipient accepted: no DATA, all results report the rejection.
	 *	@covers		::send
	 */
	public function testNoRecipientAccepted(): void
	{
		$socket		= new FakeServerSocket();
		$socket->rcptAnswers	= ['empfaenger@example.org' => '550 5.1.1 User unknown'];
		$results	= $this->createTransport( $socket, 25 )->send( $this->createMessage() );

		self::assertCount( 1, $results );
		self::assertSame( Result::STATUS_ERROR, $results[0]->getStatus() );
		self::assertSame( 'User unknown', preg_replace( '/^5\.1\.1 /', '', (string) $results[0]->getMessage() ) );
		self::assertNotContains( 'DATA', $socket->getCommands() );
		self::assertNotContains( 'QUIT', $socket->getCommands(), 'QUIT sent by socket on close only' );
	}

	private function createTransport( Socket $socket, int $port ): SMTP
	{
		return ( new SMTP( 'smtp.example.org', $port, 'user@example.org', 'geheim123' ) )->setSocket( $socket );
	}

	private function createMessage(): Message
	{
		return Message::getInstance()
			->setSender( 'user@example.org', 'Absender' )
			->addRecipient( 'empfaenger@example.org', 'Empfänger' )
			->setSubject( 'Test' )
			->addText( 'Hallo' );
	}
}

/**
 *	Fake SMTP server: answers commands in order and records them (credentials as "***").
 */
class FakeServerSocket extends Socket
{
	public bool $cryptoEnabled	= FALSE;

	/** @var array<string,string> $rcptAnswers	Answers to RCPT TO by address, default: accepted */
	public array $rcptAnswers	= [];

	/** @var string $data	Message content as sent after DATA */
	public string $data			= '';

	/** @var array<int,string> */
	private array $commands		= [];

	/** @var array<int,string> */
	private array $lines		= [];
	private bool $greetedAfterTls	= FALSE;
	private int $authStep		= 0;
	private string $lastCommand	= '';

	/**
	 *	@param		bool			$strict			Reject AUTH without EHLO after STARTTLS
	 *	@param		string|NULL		$loginAnswer	Answer to the password, default: accepted
	 *	@param		string|NULL		$localAddress	Local address of the connection
	 */
	public function __construct(
		private readonly bool $strict = FALSE,
		private readonly ?string $loginAnswer = NULL,
		private readonly ?string $localAddress = NULL,
	)
	{
		parent::__construct( 'smtp.example.org', 587 );
	}

	public function getLocalAddress(): ?string
	{
		return $this->localAddress;
	}

	public function isImplicitTlsRequested(): bool
	{
		return NULL !== $this->implicitCrypto;
	}

	/**
	 *	@return		array<int,string>		Commands with arguments, credentials and content left out
	 */
	public function getLines(): array
	{
		return $this->lines;
	}

	public function open( bool $forceReopen = FALSE ): Response
	{
		return $this->answer( '220 smtp.example.org ESMTP' );
	}

	public function close(): self
	{
		return $this;
	}

	public function enableCrypto( bool $enable, int $crypto = STREAM_CRYPTO_METHOD_ANY_CLIENT ): self
	{
		$this->cryptoEnabled	= $enable;
		return $this;
	}

	public function sendChunk( string $content ): bool
	{
		$line	= rtrim( $content, "\r\n" );
		if( in_array( $this->authStep, [1, 2], TRUE ) ){
			$this->commands[]	= '***';
			$this->lastCommand	= 'AUTH'.$this->authStep;
			$this->authStep++;
			return TRUE;
		}
		if( 'DATA' === $this->lastCommand ){								//  message content, then "."
			$this->data			= $content;
			$this->lastCommand	= 'CONTENT';
			return TRUE;
		}
		$command	= strtoupper( strtok( $line, ' :' ) ?: '' );
		if( 'CONTENT' === $this->lastCommand && '.' === $line )
			$command	= 'END';
		if( 'EHLO' === $command && $this->cryptoEnabled )
			$this->greetedAfterTls	= TRUE;
		if( 'AUTH' === $command )
			$this->authStep	= 1;
		if( 'END' !== $command ){
			$this->commands[]	= $command;
			$this->lines[]		= $line;
		}
		$this->lastCommand	= $command;
		return TRUE;
	}

	public function readResponse( int $length = 1024 ): Response
	{
		return $this->answer( match( $this->lastCommand ){
			'EHLO'		=> '250 smtp.example.org',
			'STARTTLS'	=> '220 2.0.0 Ready to start TLS',
			'AUTH'		=> $this->strict && $this->cryptoEnabled && !$this->greetedAfterTls
				? '503 5.5.1 Send hello first'
				: '334 VXNlcm5hbWU6',
			'AUTH1'		=> '334 UGFzc3dvcmQ6',
			'AUTH2'		=> $this->loginAnswer ?? '235 2.7.0 Authentication successful',
			'MAIL'		=> '250 2.1.0 OK',
			'RCPT'		=> $this->rcptAnswers[trim( (string) strrchr( (string) end( $this->lines ), '<' ), '<>' )] ?? '250 2.1.5 OK',
			'DATA'		=> '354 Start mail input',
			'END'		=> '250 2.0.0 Queued',
			default		=> '500 5.5.2 Unknown command',
		} );
	}

	/**
	 *	@return		array<int,string>
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	private function answer( string $line ): Response
	{
		$response	= new Response( (int) substr( $line, 0, 3 ) );
		$response->setMessage( substr( $line, 4 ) );
		$response->setResponse( [$line] );
		return $response;
	}
}
