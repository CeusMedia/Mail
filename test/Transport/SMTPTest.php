<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( __DIR__ ).'/bootstrap.php';

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Mailbox;
use \CeusMedia\Mail\Transport\SMTP;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Transport\SMTP
 */
class Transport_SMTPTest extends TestCase
{
	protected $receiveLoopSleep			= 1;
	protected $receiveLoopTimeout		= 120;

	/**
	 *	@covers		::send
	 */
	public function testSend_Mocked(){
		$message	= Message::create()
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
			$this->assertTrue( is_object( $returned ) );

			$actual	= $returned instanceof SMTP;
			$this->assertTrue( $actual );
		}
		catch( \Exception $e ){
//			print_r( $socket->getLog() );
			$this->fail( $e );
		}
	}

	/**
	 *	@covers		::send
	 */
	public function testSend(){
		$configSender		= $this->requireSenderConfig();
		$configReceiver		= $this->requireReceiverConfig();

		$subject	= 'Test-Automation-Message #'.\Alg_ID::uuid();

		/*  --  SENDING  --  */
		$smtp		= new SMTP( $configSender->get( 'server.host' ) );			//  get SMTP instance
		$smtp->setPort( (int) $configSender->get( 'server.port' ) );
		$smtp->setUsername( $configSender->get( 'auth.username' ) );
		$smtp->setPassword( $configSender->get( 'auth.password' ) );

		$recipient	=  Address::create()
			->set( $configReceiver->get( 'mailbox.address' ) )
			->setName( $configReceiver->get( 'mailbox.name' ) );
		$sender		=  Address::create()
			->set( $configSender->get( 'mailbox.address' ) )
			->setName( $configSender->get( 'mailbox.name' ) );
		$message	= Message::create()
			->addRecipient( $recipient )
			->setSender( $sender )
			->setSubject( $subject )
			->addText( join( Message::$delimiter, array(
				'This is a test message for automated testing.'.Message::$delimiter,
				'Subject:  '.$subject,
				'Library:  CeusMedia\Mail (https://github.com/CeusMedia/Mail)',
				'Source:   Transport\SMTP',
				'Client:   '.Message::create()->getUserAgent(),
				'Date:     '.date( 'Y-m-d H:i:s' ),
			) ) );
		$isMailSent		= $smtp->send( $message );
		if( !$isMailSent ){
			$error	= (object) $smtp->getConnectError();
			$this->markTestSkipped( 'Reason: Sender mailbox connection failed: '.$error->message.' ('.$error->number.')' );
		}

		/*  --  RECEIVING  --  */
		$mailbox	= Mailbox::getInstance(
			$configReceiver->get( 'server.host' ),
			$configReceiver->get( 'mailbox.address' ),
			$configReceiver->get( 'auth.password' )
 		)->setSecure( TRUE, FALSE );
		if( !$mailbox->connect( FALSE ) )
			$this->markTestSkipped( 'Reason: Receiver mailbox connection failed' );

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
				sleep( $this->receiveLoopSleep );
				$loopTime	+= $this->receiveLoopSleep;
			}
		} while( $loopTime < $this->receiveLoopTimeout );
		$this->assertTrue( $isMailReceived );
	}

	public function _testSend_Virus(){
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

		$recipient	=  Address::create()
			->set( $configReceiver->get( 'mailbox.address' ) )
			->setName( $configReceiver->get( 'mailbox.name' ) );
		$sender		=  Address::create()
			->set( $configSender->get( 'mailbox.address' ) )
			->setName( $configSender->get( 'mailbox.name' ) );
		$message	= Message::create()
			->addRecipient( $recipient )
			->setSender( $sender )
			->setSubject( $subject )
			->addAttachment( $filePath )
			->addText( join( Message::$delimiter, array(
				'This is a test message for automated testing.'.Message::$delimiter,
				'Subject:  '.$subject,
				'Library:  CeusMedia\Mail (https://github.com/CeusMedia/Mail)',
				'Source:   Transport\SMTP',
				'Client:   '.Message::create()->getUserAgent(),
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
		$mailbox	= Mailbox::getInstance(
			$configSender->get( 'server.host' ),
			$configSender->get( 'mailbox.address' ),
			$configSender->get( 'auth.password' )
 		)->setSecure( TRUE, FALSE );
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
		$this->assertTrue( !$isMailReceived );
	}
}

class SmtpSocketMock extends \CeusMedia\Mail\Transport\SMTP\Socket{

	protected $lastChunk;
	protected $nextResponses	= array();
	protected $status			= 0;
	protected $log				= array();

	public function __construct(){}

	public function close(){
		$this->connection	= FALSE;
	}

	public function enableCrypto( $enable, $crypto ){
		return TRUE;
	}

	protected function getFakeAnswer(){
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

	public function getLog(){
		return $this->log;
	}

	public function open( $forceReopen = FALSE ){
		$this->connection	= TRUE;
	}

	public function readResponse( $length ){
		$response	= $this->getFakeAnswer();
		$raw		= $response[0].' '.$response[1];
//		print( PHP_EOL.' < '. $raw );
		$this->log[]	= trim( ' < '. $raw );
		return (object) array(
			'code'		=> $response[0],
			'message'	=> $response[1],
			'raw'		=> array( $raw ),
		);
	}

	public function sendChunk( $message ){
		$this->lastChunk	= $message;
//		print( PHP_EOL.' > '.$message );
		$this->log[]	= trim( ' > '. $message );
		return strlen( $message );
	}
}
