<?php
/**
 *	Unit test for Client.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit
 */

namespace CeusMedia\MailTest\Unit;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Client;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\MailTest\TestCase;
use RuntimeException;
use ReflectionClass;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Client
 */
class ClientTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance(): void
	{
		$client	= Client::getInstance();
		self::assertInstanceOf( Client::class, $client );
	}

	/**
	 *	@covers		::createAddress
	 */
	public function testCreateAddress(): void
	{
		$client	= Client::getInstance();
		$address	= $client->createAddress();
		self::assertInstanceOf( Address::class, $address );
	}

	/**
	 *	@covers		::createMessage
	 */
	public function testCreateMessage(): void
	{
		$client	= Client::getInstance();
		$message	= $client->createMessage();
		self::assertInstanceOf( Message::class, $message );
	}

	/**
	 *	@covers		::getMailbox
	 */
	public function testGetMailboxThrowsWhenNotSet(): void
	{
		$client	= Client::getInstance();
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No mailbox set' );
		$client->getMailbox();
	}

	/**
	 *	@covers		::setMailbox
	 *	@covers		::getMailbox
	 */
	public function testSetAndGetMailbox(): void
	{
		$client	= Client::getInstance();
		//  Create a mock Connection, then a real Mailbox — the mock connection
		//  prevents any real IMAP calls, and the destructor is safe.
		$connection	= $this->createMock( \CeusMedia\Mail\Mailbox\Connection::class );
		$connection->method( 'disconnect' )->willReturn( TRUE );
		$mailbox	= new \CeusMedia\Mail\Mailbox( $connection );
		self::assertSame( $client, $client->setMailbox( $mailbox ) );
		self::assertInstanceOf( \CeusMedia\Mail\Mailbox::class, $client->getMailbox() );
	}

	/**
	 *	@covers		::getTransport
	 */
	public function testGetTransportThrowsWhenNotSet(): void
	{
		$client	= Client::getInstance();
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No transport set' );
		$client->getTransport();
	}

	/**
	 *	@covers		::setTransport
	 *	@covers		::getTransport
	 */
	public function testSetAndGetTransport(): void
	{
		$client	= Client::getInstance();
		$transport	= $this->createMock( SMTP::class );
		self::assertSame( $client, $client->setTransport( $transport ) );
		self::assertInstanceOf( SMTP::class, $client->getTransport() );
	}

	/**
	 *	@covers		::sendMessage
	 */
	public function testSendMessageThrowsWhenNotSet(): void
	{
		$client	= Client::getInstance();
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No transport set' );
		$client->sendMessage( Message::getInstance() );
	}

	/**
	 *	@covers		::createSearch
	 */
	public function testCreateSearch(): void
	{
		$client	= Client::getInstance();
		$search	= $client->createSearch();
		self::assertNotNull( $search );
	}
}
