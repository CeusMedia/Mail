<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Transport
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Integration\Transport;

use CeusMedia\Common\Alg\ID;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection as MailboxConnection;
use CeusMedia\Mail\Transport\Result;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\Mail\Transport\SMTP\Response as SmtpResponse;
use CeusMedia\Mail\Transport\SMTP\Socket as SmtpSocket;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Transport
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Transport\SMTP
 */
class SMTPTest extends TestCase
{
	protected int $receiveLoopSleep			= 1;
	protected int $receiveLoopTimeout		= 120;

	/**
	 *	@covers		::send
	 */
	public function testSend_Mocked()
	{
		$message	= Message::getInstance()
			->addRecipient( 'receiver@muster-server.tld' )
			->setSender( 'sender@muster-server.tld' )
			->addText( 'This is a <b>test</b>.' )
			->setSubject( 'This is a test' );

//		print( \CeusMedia\Mail\Message\Renderer::render( $message ) );

		$smtp		= new SMTP( '_not_relevant_' );	//  get SMTP instance
		$socket		= new SmtpSocketMock();										//  create mocking replacement for default SMTP socket
		$smtp->setSocket( $socket );											//  set mocking SMTP socket
		$smtp->setAuth( 'username', 'password' );								//  auth is supported by mock
		$smtp->setSecure( TRUE );												//  SSL is supported by mock

		try{
			$returned	= $smtp->send( $message );								//  try to send mail
		}
		catch( \Exception $e ){
//			print_r( $socket->getLog() );
			$this->fail( $e->getMessage() );
		}
		self::assertTrue( is_array( $returned ) );
		self::assertTrue( is_object( current( $returned ) ) );

		$first	= current( $returned );
		self::assertTrue( $first instanceof Result );
	}

	/**
	 *	@covers		::send
	 *	@covers		::sendChunk
	 *	@covers		::checkResponse
	 *	@covers		::setHost
	 *	@covers		::setPort
	 *	@covers		::setUsername
	 *	@covers		::setPassword
	 */
	public function testSend()
	{
		$configSender		= $this->requireSenderConfig();
		$configReceiver		= $this->requireReceiverConfig();

		$subject	= 'Test-Automation-Message #'. ID::uuid();

		/*  --  SENDING  --  */
		$smtp		= new SMTP( $configSender->get( 'server.host' ) );			//  get SMTP instance
		$smtp->setPort( (int) $configSender->get( 'server.port' ) );
		$smtp->setUsername( $configSender->get( 'auth.username' ) );
		$smtp->setPassword( $configSender->get( 'auth.password' ) );

		$recipient	=  Address::getInstance()
			->set( $configReceiver->get( 'mailbox.address' ) )
			->setName( $configReceiver->get( 'mailbox.name' ) );
		$sender		=  Address::getInstance()
			->set( $configSender->get( 'mailbox.address' ) )
			->setName( $configSender->get( 'mailbox.name' ) );
		$message	= Message::getInstance()
			->addRecipient( $recipient )
			->setSender( $sender )
			->setSubject( $subject )
			->addText( join( Message::$delimiter, array(
				'This is a test message for automated testing.'.Message::$delimiter,
				'Subject:  '.$subject,
				'Library:  CeusMedia\Mail (https://github.com/CeusMedia/Mail)',
				'Source:   Transport\SMTP',
				'Client:   '.Message::getInstance()->getUserAgent(),
				'Date:     '.date( 'Y-m-d H:i:s' ),
			) ) );
		$isMailSent		= $smtp->send( $message );
		if( !$isMailSent ){
			$error	= (object) $smtp->getConnectError();
			$this->markTestSkipped( 'Reason: Sender mailbox connection failed: '.$error->message.' ('.$error->number.')' );
		}

		/*  --  RECEIVING  --  */
		$connection	= MailboxConnection::getInstance(
			$configReceiver->get( 'server.host' ),
			$configReceiver->get( 'mailbox.address' ),
			$configReceiver->get( 'auth.password' )
		)->setSecure( TRUE, FALSE );
		;
		if( !$connection->connect() )
			$this->markTestSkipped( 'Reason: Receiver mailbox connection failed' );

		$mailbox	= Mailbox::getInstance($connection);

		$loopTime		= 0;
		$isMailReceived	= FALSE;
		$searchCriteria	= array( 'SUBJECT "'.$subject.'"' );
		do{
			$mailIds	= $mailbox->index( $searchCriteria );
			if( count( $mailIds ) ){
				foreach( $mailIds as $mailId )
					$mailbox->removeMail( $mailId, TRUE );
				$isMailReceived	= TRUE;
				break;
			}
			else{
				sleep( $this->receiveLoopSleep );
				$loopTime	+= $this->receiveLoopSleep;
			}
		} while( $loopTime < $this->receiveLoopTimeout );
		self::assertTrue( $isMailReceived );
	}

	public function _testSend_Virus()
	{
		$configSender		= $this->getSenderConfig();
		$configReceiver		= $this->getReceiverConfig();

		$filePath	= $this->pathTests.'data/test_virus_eicar.txt';
		$subject	= 'Test-Automation-Message #EICAR-'.\Alg_ID::uuid();
		$subject	= 'Test-Automation-Message #EICAR-';

		/*  --  SENDING  --  */
		$smtp		= new SMTP( $configSender->get( 'server.host' ) );			//  get SMTP instance
		$smtp->setPort( $configSender->get( 'server.port' ) );
		$smtp->setUsername( $configSender->get( 'auth.username' ) );
		$smtp->setPassword( $configSender->get( 'auth.password' ) );

		$recipient	=  Address::getInstance()
			->set( $configReceiver->get( 'mailbox.address' ) )
			->setName( $configReceiver->get( 'mailbox.name' ) );
		$sender		=  Address::getInstance()
			->set( $configSender->get( 'mailbox.address' ) )
			->setName( $configSender->get( 'mailbox.name' ) );
		$message	= Message::getInstance()
			->addRecipient( $recipient )
			->setSender( $sender )
			->setSubject( $subject )
			->addAttachment( $filePath )
			->addText( join( Message::$delimiter, array(
				'This is a test message for automated testing.'.Message::$delimiter,
				'Subject:  '.$subject,
				'Library:  CeusMedia\Mail (https://github.com/CeusMedia/Mail)',
				'Source:   Transport\SMTP',
				'Client:   '.Message::getInstance()->getUserAgent(),
				'Date:     '.date( 'Y-m-d H:i:s' ),
			) ) );
		$isMailSent		= $smtp->send( $message );
		if( !$isMailSent ){
			$error	= (object) $smtp->getConnectError();
			$this->markTestSkipped( 'Reason: Sender mailbox connection failed: '.$error->message.' ('.$error->number.')' );
		}

		/*  --  RECEIVING  --  */
		$receiveLoopSleep	= 10;
		$receiveLoopTimeout	= 300;
		$mailbox	= Mailbox::getInstance( MailboxConnection::getInstance(
			$configSender->get( 'server.host' ),
			$configSender->get( 'mailbox.address' ),
			$configSender->get( 'auth.password' )
 		)->setSecure( TRUE, FALSE ) );
		if( !$mailbox->connect( FALSE ) )
			$this->markTestSkipped( sprintf(
				'Reason: Receiver mailbox "%s" connection failed',
				$configSender->get( 'mailbox.address' )
			) );
		$loopTime		= 0;
		$isMailReceived	= FALSE;
		$searchCriteria	= array( 'SUBJECT "'.$subject.'"' );
		do{
			$mailIds	= $mailbox->index( $searchCriteria );
			if( count( $mailIds ) ){
				foreach( $mailIds as $mailId )
					$mailbox->removeMail( $mailId );
				$isMailReceived	= TRUE;
				break;
			}
			else{
				sleep( $receiveLoopSleep );
				$loopTime	+= $receiveLoopSleep;
			}
		} while( $loopTime < $receiveLoopTimeout );
		self::assertTrue( !$isMailReceived );
	}
}

class SmtpSocketMock extends SmtpSocket
{
	protected $lastChunk;
	protected $nextResponses	= [];
	protected $status			= 0;
	protected $log				= [];

	public function __construct()
	{
		$this->setHost( 'notimportant.invalid.tld' );
		$this->setPort( 1 );
	}

	public function close(): SmtpSocket
	{
		$this->connection	= NULL;
		return $this;
	}

	public function enableCrypto( bool $enable, ?int $crypto = NULL ): SmtpSocket
	{
		return $this;
	}

	protected function getFakeAnswer()
	{
		if( count( $this->nextResponses ) > 0 )
			return array_shift( $this->nextResponses );
		$last	= trim( $this->lastChunk );
		if( !$last || $last === "STARTTLS" )
			return [220, ""];
		if( $last === "DATA" )
			return [354, ""];
		if( $last === "QUIT" )
			return [221, ""];
		if( $last === "." )
			return [250, ""];
		if( $last === "AUTH LOGIN" ){
			$this->nextResponses	= array( [334, ""], [235, ""] );
			return [334, ""];
		}
		if( preg_match( '/^(EHLO|HELO|MAIL FROM:|RCPT TO:) /', $last ) )
			return [250, ""];
		return [0, "Unmocked request"];
	}

	public function getLog()
	{
		return $this->log;
	}

	public function open( bool $forceReopen = FALSE ): Response
	{
		$this->connection	= NULL;
		return new Response();
	}

	public function readResponse( int $length = 1024 ): SmtpResponse
	{
		$response	= $this->getFakeAnswer();
		$raw		= $response[0].' '.$response[1];
//		print( PHP_EOL.' < '. $raw );
		$this->log[]	= trim( ' < '. $raw );
		$response	= new SmtpResponse( $response[0], $response[1] );
		$response->setResponse( array( $raw ) );
		return $response;
	}

	public function sendChunk( string $content ): bool
	{
		$this->lastChunk	= $content;
//		print( PHP_EOL.' > '.$content );
		$this->log[]	= trim( ' > '. $content );
		return 0 < strlen( $content );
	}
}
