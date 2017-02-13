<?php
/**
 *	Unit test for mail message.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once __DIR__.'/bootstrap.php';
/**
 *	Unit test for mail message.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class MessageTest extends PHPUnit_Framework_TestCase
{
	public function testAddText(){
		$text		= "TestText123";
		$part		= new \CeusMedia\Mail\Part\Text($text);
		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->addText($text);

		$parts	= $message->getParts();
		$this->assertEquals( $parts[0], $part );
	}

	public function testAddHtml(){
		$html		= "<b>TestText123</b>";
		$part		= new \CeusMedia\Mail\Part\HTML($html);
		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->addHtml($html);

		$parts	= $message->getParts();
		$this->assertEquals( $parts[0], $part );
	}

	public function testAddHtmlImage(){
		$fileName	= __DIR__."/../logo.png";
		$part		= new \CeusMedia\Mail\Part\InlineImage('id', $fileName);
		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->addHtmlImage('id', $fileName);

		$parts	= $message->getParts( TRUE );
		$this->assertEquals( $parts[0], $part );
	}

	public function testEmbedImage(){
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$fileName	= __DIR__."/../logo.png";
		$part		= new \CeusMedia\Mail\Part\InlineImage('id', $fileName);
		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->embedImage('id', $fileName);

		$parts	= $message->getParts( TRUE );
		$this->assertEquals( $parts[0], $part );
	}

	public function testGetAndSetSender(){
		$message	= \CeusMedia\Mail\Message::getInstance();
		$creation	= $message->setSender( "test@example.com" );
		$this->assertEquals( $message, $creation );

		$assertion	= new \CeusMedia\Mail\Participant( "test@example.com" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );

		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->setSender( new \CeusMedia\Mail\Participant( "test@example.com" ) );
		$assertion	= new \CeusMedia\Mail\Participant( "test@example.com" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );

		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->setSender( "test@example.com", "Test Name" );
		$assertion	= new \CeusMedia\Mail\Participant( "test@example.com" );
		$assertion->setName( "Test Name" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );

		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->setSender( new \CeusMedia\Mail\Participant( "test@example.com" ), "Test Name" );
		$assertion	= new \CeusMedia\Mail\Participant( "test@example.com" );
		$assertion->setName( "Test Name" );
		$creation	= $message->getSender();
		$this->assertEquals( $assertion, $creation );
	}

	public function testGetAndSetSubject(){
		$subject	= "Test Subject";
		$message	= \CeusMedia\Mail\Message::getInstance();
		$creation	= $message->setSubject( $subject );
		$this->assertEquals( $message, $creation );

		$this->assertEquals( $subject, $message->getSubject() );
	}

	/**
	 *	@deprecated		use testGetAndSetUserAgent instead
	 *	@todo   		to be removed in 1.1
	 */
	public function testGetAndSetAgent(){
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$agent		= "Test User Agent";
		$message	= \CeusMedia\Mail\Message::getInstance();
		$creation	= $message->setAgent( $agent );
		$this->assertEquals( $message, $creation );

		$this->assertEquals( $agent, $message->getAgent() );
	}

	public function testGetAndSetUsetAgent(){
		$agent		= "Test User Agent";
		$message	= \CeusMedia\Mail\Message::getInstance();
		$creation	= $message->setUserAgent( $agent );
		$this->assertEquals( $message, $creation );

		$this->assertEquals( $agent, $message->getUserAgent() );
	}

	public function testAddAndGetRecipients(){
		$message	= \CeusMedia\Mail\Message::getInstance();

		$receiverTo		= new \CeusMedia\Mail\Participant( "receiver_to@example.com" );
		$receiverCc1	= new \CeusMedia\Mail\Participant( "receiver_cc1@example.com" );
		$receiverCc2	= new \CeusMedia\Mail\Participant( "receiver_cc2@example.com" );
		$receiverCc2->setName( "Test Name 1" );
		$receiverBcc1	= new \CeusMedia\Mail\Participant( "receiver_bcc1@example.com" );
		$receiverBcc2	= new \CeusMedia\Mail\Participant( "receiver_bcc2@example.com" );
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
			$receiverTo,
			$receiverCc1,
			$receiverCc2,
			$receiverBcc1,
			$receiverBcc2,
		);
		$this->assertEquals( $message->getRecipients(), $assertion );
	}

	/**
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testSetSenderException(){
		$message	= \CeusMedia\Mail\Message::getInstance();
		$message->setSender( array( 'invalid' ) );
	}

	public function testEncodeIfNeeded(){
		$creation	= \CeusMedia\Mail\Message::encodeIfNeeded( "ÄÖÜ" );
		$assertion	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $creation, $assertion );

		$creation	= \CeusMedia\Mail\Message::encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$assertion	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $creation, $assertion );
	}

	/**
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testEncodeIfNeededException(){
		\CeusMedia\Mail\Message::encodeIfNeeded( "ÄÖÜ", "_invalid_" );
	}

	public function testAddAndGetAttachments(){
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$attachment1	= new \CeusMedia\Mail\Part\Attachment();
		$attachment1->setFile( __FILE__ );
		$attachment2	= new \CeusMedia\Mail\Part\Attachment();
		$attachment2->setFile( __FILE__ );

		$message	= \CeusMedia\Mail\Message::getInstance();
		$creation	= $message->addAttachment( $attachment1 );
		$this->assertEquals( $message, $creation );
		$creation	= $message->addAttachment( $attachment2 );
		$this->assertEquals( $message, $creation );

		$creation	= array( $attachment1, $attachment2 );
		$this->assertEquals( $creation, $message->getAttachments() );
	}

	public function testAttachFileAndGetAttachments(){
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$message	= \CeusMedia\Mail\Message::getInstance();
		$creation	= $message->attachFile( __FILE__ );
		$this->assertEquals( $message, $creation );
		$creation	= $message->attachFile( __FILE__ );
		$this->assertEquals( $message, $creation );

		$attachment	= new \CeusMedia\Mail\Part\Attachment();
		$attachment->setFile( __FILE__ );

		$creation	= array( $attachment, $attachment );
		$this->assertEquals( $creation, $message->getAttachments() );
	}
}
