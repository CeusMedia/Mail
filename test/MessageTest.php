<?php
/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once __DIR__.'/bootstrap.php';

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Collection as AddressCollection;
use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Part\Attachment;
use \CeusMedia\Mail\Message\Part\HTML;
use \CeusMedia\Mail\Message\Part\InlineImage;
use \CeusMedia\Mail\Message\Part\Text;
use \CeusMedia\Mail\Message\Renderer;

/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message
 */
class MessageTest extends PHPUnit_Framework_TestCase
{
	/**
	 *	@covers		::addText
	 */
	public function testAddText(){
		$text		= "TestText123";
		$part		= new Text($text);
		$message	= Message::getInstance();
		$message->addText($text);

		$parts	= $message->getParts();
		$this->assertEquals( $parts[0], $part );
	}

	/**
	 *	@covers		::addHtml
	 */
	public function testAddHtml(){
		$html		= "<b>TestText123</b>";
		$part		= new HTML($html);
		$message	= Message::getInstance();
		$message->addHtml($html);

		$parts	= $message->getParts();
		$this->assertEquals( $parts[0], $part );
	}

	/**
	 *	@covers		::addHtmlImage
	 */
	public function testAddHtmlImage(){
		$fileName	= __DIR__."/../demo/outbox.png";
		$part		= new InlineImage('id', $fileName);
		$message	= Message::getInstance();
		$message->addHtmlImage('id', $fileName);

		$parts	= $message->getParts( TRUE );
		$this->assertEquals( $parts[0], $part );
	}

	/**
	 *	@covers		::getSender
	 *	@covers		::setSender
	 */
	public function testGetAndSetSender(){
		$message	= Message::getInstance();
		$actual	= $message->setSender( "test@example.com" );
		$this->assertEquals( $message, $actual );

		$expected	= new Address( "test@example.com" );
		$actual	= $message->getSender();
		$this->assertEquals( $expected, $actual );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ) );
		$expected	= new Address( "test@example.com" );
		$actual	= $message->getSender();
		$this->assertEquals( $expected, $actual );

		$message	= Message::getInstance();
		$message->setSender( "test@example.com", "Test Name" );
		$expected	= new Address( "test@example.com" );
		$expected->setName( "Test Name" );
		$actual	= $message->getSender();
		$this->assertEquals( $expected, $actual );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ), "Test Name" );
		$expected	= new Address( "test@example.com" );
		$expected->setName( "Test Name" );
		$actual	= $message->getSender();
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::getSubject
	 *	@covers		::setSubject
	 */
	public function testGetAndSetSubject(){
		$subject	= "Test Subject - Test Subject - Test Subject - Test Subject - Test Subject - Test Subject - Test Subject";
		$message	= Message::getInstance();
		$actual	= $message->setSubject( $subject );
		$this->assertEquals( $message, $actual );

		$this->assertEquals( $subject, $message->getSubject() );

		$message->addText( 'Test Text.' );


		$subject	= "[Gruppenpost] Gruppe \"Deli_124\": Mike ist beigetreten und benötigt Freigabe";

/*		$headers	= new \CeusMedia\Mail\Message\Header\Section();
		$headers->addFieldPair( 'Subject', $subject );
		var_export( $headers->getField( 'Subject' ) );
		print( $headers->toString( TRUE ) );*/

		$message->setSubject( $subject );
		$message->setSender( 'kriss@ceusmedia.de' );
		$message->addRecipient( 'test2@ceusmedia.de' );
//		print( Renderer::render( $message ) );die;
		$smtp	= new \CeusMedia\Mail\Transport\SMTP( 'mail.itflow.de', 25, 'kriss@ceusmedia.de', 'dialog');
		$smtp->send( $message );

	}

	/**
	 *	@covers		::getUserAgent
	 *	@covers		::setUserAgent
	 */
	public function testGetAndSetUserAgent(){
		$agent		= "Test User Agent";
		$message	= Message::getInstance();
		$actual	= $message->setUserAgent( $agent );
		$this->assertEquals( $message, $actual );

		$this->assertEquals( $agent, $message->getUserAgent() );
	}

	/**
	 *	@covers		::addRecipient
	 *	@covers		::getRecipients
	 */
	public function testAddAndGetRecipients(){
		$message	= Message::getInstance();

		$receiverTo		= new Address( "receiver_to@example.com" );
		$receiverCc1	= new Address( "receiver_cc1@example.com" );
		$receiverCc2	= new Address( "receiver_cc2@example.com" );
		$receiverCc2->setName( "Test Name 1" );
		$receiverBcc1	= new Address( "receiver_bcc1@example.com" );
		$receiverBcc2	= new Address( "receiver_bcc2@example.com" );
		$receiverBcc2->setName( "Test Name 2" );

		$actual	= $message->addRecipient( $receiverTo );
		$this->assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverCc1, NULL, 'cc' );
		$this->assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverCc2, "Test Name 1", 'cc' );
		$this->assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverBcc1, NULL, 'bcc' );
		$this->assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverBcc2, "Test Name 2", 'bcc' );
		$this->assertEquals( $message, $actual );

		$expected	= array(
			'to'	=> new AddressCollection( array(
				new Address( 'receiver_to@example.com' ),
			) ),
			'cc'	=> new AddressCollection( array(
				new Address( 'receiver_cc1@example.com' ),
				new Address( 'Test Name 1 <receiver_cc2@example.com>' ),
			) ),
			'bcc'	=> new AddressCollection( array(
				new Address( 'receiver_bcc1@example.com' ),
				new Address( 'Test Name 2 <receiver_bcc2@example.com>' ),
			) ),
		);
		$this->assertEquals( $expected, $message->getRecipients() );
	}

	/**
	 *	@covers		::setSender
	 */
	public function testSetSenderException(){
		$this->expectException( 'InvalidArgumentException' );
		$message	= Message::getInstance();
		$message->setSender( array( 'invalid' ) );
	}

	/**
	 *	@covers		::addAttachment
	 *	@covers		::getAttachments
	 */
	public function testAddAndGetAttachments(){
//		$this->expectException( 'PHPUnit_Framework_Error' );
		$attachment1	= new Attachment();
		$attachment1->setFile( __FILE__ );
		$attachment2	= new Attachment();
		$attachment2->setFile( __FILE__ );

		$message	= Message::getInstance();
		$actual	= $message->addPart( $attachment1 );
		$this->assertEquals( $message, $actual );
		$actual	= $message->addAttachment( __FILE__ );
		$this->assertEquals( $message, $actual );

		$actual	= array( $attachment1, $attachment2 );
		$this->assertEquals( $actual, $message->getAttachments() );
	}
}
