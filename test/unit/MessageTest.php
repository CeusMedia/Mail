<?php
/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part\Attachment;
use CeusMedia\Mail\Message\Part\HTML;
use CeusMedia\Mail\Message\Part\InlineImage;
use CeusMedia\Mail\Message\Part\Text;
use CeusMedia\Mail\Message\Part\Mail;
use PHPUnit_Framework_TestCase as PhpUnitTestCase;

/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message
 */
class MessageTest extends PhpUnitTestCase
{
	/**
	 *	@covers		::addAttachment
	 *	@covers		::getAttachments
	 *	@covers		::hasAttachments
	 *	@covers		::addPart
	 *	@covers		::getParts
	 */
	public function testAddGetAndHasAttachments()
	{
		$subject1	= new Attachment();
		$subject1->setFile( __FILE__ );
		$subject2	= new Attachment();
		$subject2->setFile( __FILE__ );

		$message	= Message::getInstance();
		$this->assertFalse( $message->hasAttachments() );

		$actual	= $message->addPart( $subject1 );
		$this->assertEquals( $message, $actual );
		$this->assertTrue( $message->hasAttachments() );

		$actual	= $message->addAttachment( __FILE__ );
		$this->assertEquals( $message, $actual );

		$actual	= array( $subject1, $subject2 );
		$this->assertEquals( $actual, $message->getAttachments() );
		$this->assertEquals( $actual, $message->getParts() );
		$this->assertEquals( array(), $message->getParts( FALSE ) );
	}

	/**
	 *	@covers		::addText
	 *	@covers		::getText
	 *	@covers		::hasText
	 *	@covers		::getParts
	 */
	public function testAddGetAndHasText()
	{
		$text		= "TestText123 ÄÖÜäöüß";
		$part		= new Text( $text );
		$message	= Message::getInstance();
		$this->assertFalse( $message->hasText() );

		$actual	= $message->addText( $text );
		$this->assertEquals( $message, $actual );
		$this->assertTrue( $message->hasText() );

		$this->assertEquals( $part, $message->getText() );
		$this->assertEquals( $text, $message->getText()->getContent() );
		$this->assertEquals( $part, $message->getParts()[0] );
	}

	/**
	 *	@covers		::addHTML
	 *	@covers		::getHTML
	 *	@covers		::hasHTML
	 *	@covers		::getParts
	 */
	public function testAddGetAndHasHTML()
	{
		$text		= "<div><b>TestText123</b> <em>ÄÖÜäöüß</em></div>";
		$part		= new HTML( $text );
		$message	= Message::getInstance();
		$this->assertFalse( $message->hasHTML() );

		$actual	= $message->addHTML( $text );
		$this->assertEquals( $message, $actual );
		$this->assertTrue( $message->hasHTML() );

		$this->assertEquals( $part, $message->getHTML() );
		$this->assertEquals( $text, $message->getHTML()->getContent() );
		$this->assertEquals( $part, $message->getParts()[0] );
	}

	/**
	*	@covers		::addInlineImage
	*	@covers		::getInlineImages
	*	@covers		::hasInlineImages
	*	@covers		::getParts
	 */
	public function testAddGetAndHasInlineImages()
	{
		$filePath	= __DIR__."/../../demo/files/outbox.png";
		$part		= new InlineImage('id');
		$part->setFile($filePath);

		$message	= Message::getInstance();
		$this->assertFalse( $message->hasInlineImages() );

		$actual		= $message->addInlineImage( 'id', $filePath );
		$this->assertEquals( $message, $actual );
		$this->assertTrue( $message->hasInlineImages() );

		$this->assertEquals( $part, $message->getInlineImages()[0] );
		$this->assertEquals( $part, $message->getParts()[0] );
		$this->assertEquals( array(), $message->getParts( TRUE, FALSE ) );
	}

	/**
	 *	@covers		::addMail
	 *	@covers		::getMails
	 *	@covers		::hasMails
	 *	@covers		::addPart
	 *	@covers		::getParts
	 */
	public function testAddGetAndHasMails()
	{
		$subject1	= new Mail( 'Mail Content 1' );
		$subject2	= new Mail( 'Mail Content 2' );

		$message	= Message::getInstance();
		$this->assertFalse( $message->hasMails() );

		$actual	= $message->addPart( $subject1 );
		$this->assertEquals( $message, $actual );
		$this->assertTrue( $message->hasMails() );

		$actual	= $message->addMail( 'Mail Content 2' );
		$this->assertEquals( $message, $actual );

		$actual	= array( $subject1, $subject2 );
		$this->assertEquals( $actual, $message->getMails() );
		$this->assertEquals( array(), $message->getParts( TRUE, TRUE, FALSE ) );
	}

	/**
	 *	@covers		::addReplyTo
	 *	@covers		::getReplyTo
	 */
	public function testAddAndGetReplyTo()
	{
		$string		= 'test1@example.com';
		$message	= Message::getInstance();
		$actual		= $message->addReplyTo( $string );
		$this->assertEquals( $message, $actual );

		$header		= $message->getHeaders()->getField( 'Reply-To' );
		$this->assertEquals( $string, $header->getValue( $string ) );

		$expected	= [new Address( $string )];
		$this->assertEquals( $expected, $message->getReplyTo() );

		$address1	= (new Address( 'test2@example.com' ))->setName( 'Test 2' );
		$actual		= $message->addReplyTo( $address1 );
		$this->assertEquals( $message, $actual );

		$header		= $message->getHeaders()->getFieldsByName( 'Reply-To' );
		$this->assertEquals( 2, count( $header ) );

		$expected	= [new Address( $string ), $address1];
		$this->assertEquals( $expected, $message->getReplyTo() );

		$address2	= new Address( 'test2@example.com' );
		$actual		= $message->addReplyTo( $address2, 'Test 3' );
		$this->assertEquals( $message, $actual );

		$header		= $message->getHeaders()->getFieldsByName( 'Reply-To' );
		$this->assertEquals( 3, count( $header ) );

		$expected	= [new Address( $string ), $address1, $address2->setName( 'Test 3' )];
		$this->assertEquals( $expected, $message->getReplyTo() );
	}

	/**
	 *	@covers		::addReplyTo
	 */
	public function testAddReplyToException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$message	= Message::getInstance();
		$message->addReplyTo( new Message() );
	}

	/**
	 *	@covers		::addRecipient
	 *	@covers		::getRecipients
	 *	@covers		::getRecipientsByType
	 */
	public function testAddAndGetRecipients()
	{
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

		$this->assertEquals( $expected['to'], $message->getRecipientsByType( 'to' ) );
		$this->assertEquals( $expected['to'], $message->getRecipientsByType( 'TO' ) );
		$this->assertEquals( $expected['cc'], $message->getRecipientsByType( 'cc' ) );
		$this->assertEquals( $expected['cc'], $message->getRecipientsByType( 'Cc' ) );
		$this->assertEquals( $expected['bcc'], $message->getRecipientsByType( 'bcc' ) );
		$this->assertEquals( $expected['bcc'], $message->getRecipientsByType( 'bcC' ) );
	}

	/**
	 *	@covers		::addRecipient
	 */
	public function testAddRecipientException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$message	= Message::getInstance();
		$message->addRecipient( new Message() );
	}

	/**
	 *	@covers		::addRecipient
	 */
	public function testAddRecipientExceptionType()
	{
		$this->expectException( 'DomainException' );
		$message	= Message::getInstance();
		$message->addRecipient( 'not_important@domain.tld', NULL, 'invalid' );
	}

	/**
	 *	@covers		::getRecipientsByType
	 */
	public function testGetRecipientsByTypeException()
	{
		$this->expectException( 'DomainException' );
		$message	= Message::getInstance();
		$message->getRecipientsByType( 'invalid' );
	}

	/**
	 *	@covers		::getSender
	 *	@covers		::setSender
	 */
	public function testGetAndSetSender()
	{
		$message	= Message::getInstance();
		$actual		= $message->setSender( "test@example.com" );
		$this->assertEquals( $message, $actual );

		$expected	= new Address( "test@example.com" );
		$this->assertEquals( $expected, $message->getSender() );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ) );
		$expected	= new Address( "test@example.com" );
		$this->assertEquals( $expected, $message->getSender() );

		$message	= Message::getInstance();
		$message->setSender( "test@example.com", "Test Name" );
		$expected	= new Address( "test@example.com" );
		$expected->setName( "Test Name" );
		$this->assertEquals( $expected, $message->getSender() );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ), "Test Name" );
		$expected	= new Address( "test@example.com" );
		$expected->setName( "Test Name" );
		$this->assertEquals( $expected, $message->getSender() );
	}

	/**
	 *	@covers		::getSubject
	 *	@covers		::setSubject
	 */
	public function testGetAndSetSubject()
	{
		$subject	= "Test Subject - Test Subject - Test Subject - Test Subject - Test Subject - Test Subject - Test Subject";
		$message	= Message::getInstance();
		$actual		= $message->setSubject( $subject );
		$this->assertEquals( $message, $actual );
		$this->assertEquals( $subject, $message->getSubject() );
	}

	/**
	 *	@covers		::getUserAgent
	 *	@covers		::setUserAgent
	 */
	public function testGetAndSetUserAgent()
	{
		$agent		= "Test User Agent";
		$message	= Message::getInstance();
		$actual	= $message->setUserAgent( $agent );
		$this->assertEquals( $message, $actual );

		$this->assertEquals( $agent, $message->getUserAgent() );
	}

	/**
	 *	@covers		::getHTML
	 */
	public function testGetHTMLException()
	{
		$this->expectException( 'RangeException' );
		$message	= Message::getInstance();
		$message->getHTML();
	}

	/**
	 *	@covers		::getText
	 */
	public function testGetTextException()
	{
		$this->expectException( 'RangeException' );
		$message	= Message::getInstance();
		$message->getText();
	}

	/**
	 *	@covers		::setSender
	 */
	public function testSetSenderException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$message	= Message::getInstance();
		$message->setSender( (object) array( 'invalid' ) );
	}

	/**
	 *	@covers		::setReadNotificationRecipient
	 */
	public function testSetReadNotificationRecipient()
	{
		$message	= Message::getInstance();
		$string		= 'Observer <observer@example.net>';
		$address	= new Address( $string );
		$actual		= $message->setReadNotificationRecipient( $address );
		$this->assertEquals( $message, $actual );

		$fields		= $message->getHeaders()->getFieldsByName( 'Disposition-Notification-To' );
		$this->assertEquals( 1, count( $fields ) );

		$field		= $message->getHeaders()->getField( 'Disposition-Notification-To' );
		$this->assertEquals( $string, $field->getValue() );


		$actual		= $message->setReadNotificationRecipient( $string );
		$this->assertEquals( $message, $actual );

		$fields		= $message->getHeaders()->getFieldsByName( 'Disposition-Notification-To' );
		$this->assertEquals( 2, count( $fields ) );

		$this->assertEquals( $string, $fields[1]->getValue() );


		$message	= Message::getInstance();
		$address	= new Address( 'observer@example.net' );

		$actual		= $message->setReadNotificationRecipient( $string, 'Observer' );
		$this->assertEquals( $message, $actual );

		$fields		= $message->getHeaders()->getFieldsByName( 'Disposition-Notification-To' );
		$this->assertEquals( 1, count( $fields ) );

		$expected	= 'Observer <observer@example.net>';
		$this->assertEquals( $expected, $fields[0]->getValue() );
	}
}
