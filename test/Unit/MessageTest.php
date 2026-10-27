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
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Message\Parser as MessageParser;
use CeusMedia\Mail\Message\Renderer as MessageRenderer;
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
		self::assertFalse( $message->hasAttachments() );

		$actual	= $message->addPart( $subject1 );
		self::assertEquals( $message, $actual );
		self::assertTrue( $message->hasAttachments() );

		$actual	= $message->addAttachment( __FILE__ );
		self::assertEquals( $message, $actual );

		$actual	= array( $subject1, $subject2 );
		self::assertEquals( $actual, $message->getAttachments() );
		self::assertEquals( $actual, $message->getParts() );
		self::assertEquals( array(), $message->getParts( FALSE ) );
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
		self::assertFalse( $message->hasText() );

		$actual	= $message->addText( $text );
		self::assertEquals( $message, $actual );
		self::assertTrue( $message->hasText() );

		self::assertEquals( $part, $message->getText() );
		self::assertEquals( $text, $message->getText()->getContent() );
		self::assertEquals( $part, $message->getParts()[0] );
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
		self::assertFalse( $message->hasHTML() );

		$actual	= $message->addHTML( $text );
		self::assertEquals( $message, $actual );
		self::assertTrue( $message->hasHTML() );

		self::assertEquals( $part, $message->getHTML() );
		self::assertEquals( $text, $message->getHTML()->getContent() );
		self::assertEquals( $part, $message->getParts()[0] );
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
		self::assertFalse( $message->hasInlineImages() );

		$actual		= $message->addInlineImage( 'id', $filePath );
		self::assertEquals( $message, $actual );
		self::assertTrue( $message->hasInlineImages() );

		self::assertEquals( $part, $message->getInlineImages()[0] );
		self::assertEquals( $part, $message->getParts()[0] );
		self::assertEquals( array(), $message->getParts( TRUE, FALSE ) );
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
		self::assertFalse( $message->hasMails() );

		$actual	= $message->addPart( $subject1 );
		self::assertEquals( $message, $actual );
		self::assertTrue( $message->hasMails() );

		$actual	= $message->addMail( 'Mail Content 2' );
		self::assertEquals( $message, $actual );

		$actual	= array( $subject1, $subject2 );
		self::assertEquals( $actual, $message->getMails() );
		self::assertEquals( array(), $message->getParts( TRUE, TRUE, FALSE ) );
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
		self::assertEquals( $message, $actual );

		$header		= $message->getHeaders()->getField( 'Reply-To' );
		self::assertEquals( $string, $header->getValue( $string ) );

		$expected	= [new Address( $string )];
		self::assertEquals( $expected, $message->getReplyTo() );

		$address1	= (new Address( 'test2@example.com' ))->setName( 'Test 2' );
		$actual		= $message->addReplyTo( $address1 );
		self::assertEquals( $message, $actual );

		//  one header listing all addresses (RFC 5322, 3.6.2)
		$header		= $message->getHeaders()->getFieldsByName( 'Reply-To' );
		self::assertEquals( 1, count( $header ) );
		self::assertEquals( 'test1@example.com, Test 2 <test2@example.com>', $header[0]->getValue() );

		$expected	= [new Address( $string ), $address1];
		self::assertEquals( $expected, $message->getReplyTo() );

		//  same address again is ignored
		$address2	= new Address( 'TEST2@example.com' );
		$actual		= $message->addReplyTo( $address2, 'Test 3' );
		self::assertEquals( $message, $actual );
		self::assertEquals( 1, count( $message->getHeaders()->getFieldsByName( 'Reply-To' ) ) );
		self::assertEquals( $expected, $message->getReplyTo() );

		//  names with umlauts are encoded, but returned decoded
		$message->addReplyTo( 'test3@example.com', 'Zoë Müller' );
		self::assertStringContainsString( '=?UTF-8?', $message->getHeaders()->getField( 'Reply-To' )->getValue() );
		self::assertEquals( 'Zoë Müller', $message->getReplyTo()[2]->getName() );
	}

	/**
	 *	To and Cc are one header each, listing all addresses (RFC 5322, 3.6.3), Bcc is not shown.
	 *	@covers		::addRecipient
	 */
	public function testRecipientHeadersAreCombined()
	{
		$message	= Message::getInstance()
			->addRecipient( 'one@example.com' )
			->addRecipient( 'two@example.com', 'Doe, John' )
			->addRecipient( 'ONE@example.com', 'Again' )										//  same address: name taken, since missing
			->addRecipient( 'two@example.com', 'Other' )										//  same address: name kept
			->addRecipient( 'copy@example.com', 'Zoë', 'cc' )
			->addRecipient( 'hidden@example.com', NULL, 'bcc' );
		$headers	= $message->getHeaders();

		self::assertCount( 1, $headers->getFieldsByName( 'To' ) );
		self::assertEquals( 'Again <one@example.com>, "Doe, John" <two@example.com>', $headers->getField( 'To' )->getValue() );
		self::assertCount( 2, $message->getRecipientsByType( 'to' ) );

		self::assertCount( 1, $headers->getFieldsByName( 'Cc' ) );
		self::assertMatchesRegularExpression( '/^=\?UTF-8\?.+\?= <copy@example\.com>$/', $headers->getField( 'Cc' )->getValue() );

		self::assertFalse( $headers->hasField( 'Bcc' ) );

		//  long lists are folded between addresses
		for( $i = 1; $i <= 10; $i++ )
			$message->addRecipient( 'receiver-number-'.$i.'@example.com' );
		$value	= $headers->getField( 'To' )->getValue();
		foreach( explode( Message::$delimiter, $value ) as $nr => $line ){
			self::assertLessThanOrEqual( 78, strlen( $line ) );
			if( 0 !== $nr )
				self::assertStringStartsWith( ' ', $line );
		}
		self::assertCount( 12, explode( ',', str_replace( '"Doe, John"', 'Doe', $value ) ) );
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
		self::assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverCc1, NULL, 'cc' );
		self::assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverCc2, "Test Name 1", 'cc' );
		self::assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverBcc1, NULL, 'bcc' );
		self::assertEquals( $message, $actual );
		$actual	= $message->addRecipient( $receiverBcc2, "Test Name 2", 'bcc' );
		self::assertEquals( $message, $actual );

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
		self::assertEquals( $expected, $message->getRecipients() );

		self::assertEquals( $expected['to'], $message->getRecipientsByType( 'to' ) );
		self::assertEquals( $expected['to'], $message->getRecipientsByType( 'TO' ) );
		self::assertEquals( $expected['cc'], $message->getRecipientsByType( 'cc' ) );
		self::assertEquals( $expected['cc'], $message->getRecipientsByType( 'Cc' ) );
		self::assertEquals( $expected['bcc'], $message->getRecipientsByType( 'bcc' ) );
		self::assertEquals( $expected['bcc'], $message->getRecipientsByType( 'bcC' ) );
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
		self::assertEquals( $message, $actual );

		$expected	= new Address( "test@example.com" );
		self::assertEquals( $expected, $message->getSender() );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ) );
		$expected	= new Address( "test@example.com" );
		self::assertEquals( $expected, $message->getSender() );

		$message	= Message::getInstance();
		$message->setSender( "test@example.com", "Test Name" );
		$expected	= new Address( "test@example.com" );
		$expected->setName( "Test Name" );
		self::assertEquals( $expected, $message->getSender() );

		$message	= Message::getInstance();
		$message->setSender( new Address( "test@example.com" ), "Test Name" );
		$expected	= new Address( "test@example.com" );
		$expected->setName( "Test Name" );
		self::assertEquals( $expected, $message->getSender() );
		self::assertEquals( 'Test Name <test@example.com>', $message->getHeaders()->getField( 'From' )->getValue() );

		//  name with umlauts is encoded in header (RFC 2047), sender is replaced, not added
		$message->setSender( "other@example.com", "Jürgen Müller" );
		self::assertCount( 1, $message->getHeaders()->getFieldsByName( 'From' ) );
		self::assertMatchesRegularExpression( '/^=\?UTF-8\?B\?.+\?= <other@example\.com>$/', $message->getHeaders()->getField( 'From' )->getValue() );
		self::assertEquals( 'Jürgen Müller', $message->getSender()->getName() );
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
		self::assertEquals( $message, $actual );
		self::assertEquals( $subject, $message->getSubject() );
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
		self::assertEquals( $message, $actual );

		self::assertEquals( $agent, $message->getUserAgent() );
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
	 *	@covers		::setReadNotificationRecipient
	 */
	public function testSetReadNotificationRecipient()
	{
		$message	= Message::getInstance();
		$string		= 'Observer <observer@example.net>';
		$address	= new Address( $string );
		$actual		= $message->setReadNotificationRecipient( $address );
		self::assertEquals( $message, $actual );

		$fields		= $message->getHeaders()->getFieldsByName( 'Disposition-Notification-To' );
		self::assertEquals( 1, count( $fields ) );

		$field		= $message->getHeaders()->getField( 'Disposition-Notification-To' );
		self::assertEquals( $string, $field->getValue() );


		$actual		= $message->setReadNotificationRecipient( $string );
		self::assertEquals( $message, $actual );

		$fields		= $message->getHeaders()->getFieldsByName( 'Disposition-Notification-To' );
		self::assertEquals( 1, count( $fields ), 'set again: replaced' );

		self::assertEquals( $string, $fields[0]->getValue() );


		$message	= Message::getInstance();
		$address	= new Address( 'observer@example.net' );

		$actual		= $message->setReadNotificationRecipient( $string, 'Observer' );
		self::assertEquals( $message, $actual );

		$fields		= $message->getHeaders()->getFieldsByName( 'Disposition-Notification-To' );
		self::assertEquals( 1, count( $fields ) );

		$expected	= 'Observer <observer@example.net>';
		self::assertEquals( $expected, $fields[0]->getValue() );

		//  name with non-ASCII characters is encoded (RFC 2047)
		$message->setReadNotificationRecipient( 'observer@example.net', 'Jürgen Müller' );
		$value		= $message->getHeaders()->getField( 'Disposition-Notification-To' )?->getValue();
		self::assertSame( 1, preg_match( '/^[\x20-\x7E\r\n\t]+$/', (string) $value ), 'ASCII only' );
		$decoded	= MessageHeaderEncoding::getInstance()->decodeAddressListIfNeeded( (string) $value );
		self::assertSame( 'Jürgen Müller <observer@example.net>', $decoded );
	}

	/**
	 *	Address headers with long encoded names: lines within 76 characters incl. header name (RFC 2047, 2).
	 */
	public function testAddressHeaderLineLength(): void
	{
		$message	= Message::getInstance()
			->setSender( 's@example.org', str_repeat( 'Jürgen ', 12 ) )
			->addRecipient( 'a@example.org', 'Bob' )
			->addRecipient( 'b@example.org', str_repeat( 'Müller ', 12 ) )
			->addReplyTo( 'r@example.org', str_repeat( 'Größe ', 12 ) );
		foreach( ['From', 'To', 'Reply-To'] as $name ){
			$header	= $message->getHeaders()->getField( $name )?->toString( TRUE );
			self::assertNotNull( $header );
			foreach( explode( Message::$delimiter, $header ) as $line )
				self::assertLessThanOrEqual( 76, strlen( $line ), $line );
		}
	}

	/**
	 *	Bcc header of a parsed message is never rendered, its recipients stay blind (RFC 5322, 3.6.3).
	 */
	public function testParsedBccHeaderIsNotRendered(): void
	{
		$raw		= "From: s@example.org\r\nTo: t@example.org\r\nBcc: secret@example.org\r\nSubject: x\r\nContent-Type: text/plain\r\n\r\nHallo";
		$message	= MessageParser::getInstance()->parse( $raw );
		self::assertSame( ['secret@example.org'], array_map( static fn( Address $a ) => $a->getAddress(), $message->getRecipientsByType( 'bcc' )->getAll() ) );

		foreach( [$message, $message->addText( 'Zweiter Teil' )] as $variant ){
			$rendered	= MessageRenderer::render( $variant );
			$headers	= (string) strstr( $rendered, Message::$delimiter.Message::$delimiter, TRUE );
			self::assertDoesNotMatchRegularExpression( '/^Bcc:/mi', $headers );
			self::assertStringNotContainsString( 'secret@example.org', $rendered );
		}
		self::assertTrue( $message->getHeaders()->hasField( 'Bcc' ), 'message headers unchanged' );
	}

	/**
	 *	Unreadable Reply-To (no usable address) is skipped by getReplyTo and replaced by addReplyTo.
	 *	@covers		::addReplyTo
	 *	@covers		::getReplyTo
	 */
	public function testAddReplyToWithUnreadableHeader(): void
	{
		$message	= Message::getInstance()->addHeaderPair( 'Reply-To', 'undisclosed-recipients:;' );
		self::assertSame( [], $message->getReplyTo() );

		$message->addReplyTo( 'reply@example.org', 'Reply' );										//  only one Reply-To (RFC 5322, 3.6)
		$values		= array_map( static fn( $field ) => $field->getValue(), $message->getHeaders()->getFieldsByName( 'Reply-To' ) );
		self::assertSame( ['Reply <reply@example.org>'], $values );
	}

	/**
	 *	Same address object added again with name: header follows the recipient list.
	 *	@covers		::addRecipient
	 */
	public function testAddSameRecipientObjectWithName(): void
	{
		$address	= new Address( 'x@example.org' );
		$message	= Message::getInstance()->addRecipient( $address )->addRecipient( $address, 'New Name' );
		self::assertSame( 'New Name <x@example.org>', $message->getHeaders()->getField( 'To' )->getValue() );
		self::assertCount( 1, $message->getRecipientsByType( 'to' ) );
	}
}
