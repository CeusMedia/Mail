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
		$creation	= $message->setSender( "test@example.com" );
		$this->assertEquals( $message, $creation );

		$assertion	= new Address( "test@example.com" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ) );
		$assertion	= new Address( "test@example.com" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );

		$message	= Message::getInstance();
		$message->setSender( "test@example.com", "Test Name" );
		$assertion	= new Address( "test@example.com" );
		$assertion->setName( "Test Name" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ), "Test Name" );
		$assertion	= new Address( "test@example.com" );
		$assertion->setName( "Test Name" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::getSubject
	 *	@covers		::setSubject
	 */
	public function testGetAndSetSubject(){
		$subject	= "Test Subject";
		$message	= Message::getInstance();
		$creation	= $message->setSubject( $subject );
		$this->assertEquals( $message, $creation );

		$this->assertEquals( $subject, $message->getSubject() );
	}

	/**
	 *	@covers		::getUserAgent
	 *	@covers		::setUserAgent
	 */
	public function testGetAndSetUserAgent(){
		$agent		= "Test User Agent";
		$message	= Message::getInstance();
		$creation	= $message->setUserAgent( $agent );
		$this->assertEquals( $message, $creation );

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

		$creation	= $message->addRecipient( $receiverTo );
		$this->assertEquals( $message, $creation );
		$creation	= $message->addRecipient( $receiverCc1, NULL, 'cc' );
		$this->assertEquals( $message, $creation );
		$creation	= $message->addRecipient( $receiverCc2, "Test Name 1", 'cc' );
		$this->assertEquals( $message, $creation );
		$creation	= $message->addRecipient( $receiverBcc1, NULL, 'bcc' );
		$this->assertEquals( $message, $creation );
		$creation	= $message->addRecipient( $receiverBcc2, "Test Name 2", 'bcc' );
		$this->assertEquals( $message, $creation );

		$assertion	= array(
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
		$this->assertEquals( $message->getRecipients(), $assertion );
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
		$creation	= $message->addPart( $attachment1 );
		$this->assertEquals( $message, $creation );
		$creation	= $message->addAttachment( __FILE__ );
		$this->assertEquals( $message, $creation );

		$creation	= array( $attachment1, $attachment2 );
		$this->assertEquals( $creation, $message->getAttachments() );
	}
}
