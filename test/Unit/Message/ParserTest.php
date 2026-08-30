<?php
/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message;

use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Message\Part\Attachment;
use CeusMedia\Mail\Message\Part\InlineImage;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 *	@covers		::getInstance
	 *	@covers		::parseAtomicBodyPart
	 *	@covers		::parseMultipartBody
	 *	@covers		::createTextPart
	 *	@covers		::createHTMLPart
	 */
	public function testParse(): void
	{
		$raw		= file_get_contents(__DIR__ . '/parserMailMultipart-plain,html.eml');
		$parser		= Parser::getInstance();
		$message	= $parser->parse( $raw );

		self::assertEquals( TRUE, $message->hasHTML() );
		self::assertEquals( TRUE, $message->hasText() );
		self::assertEquals( FALSE, $message->hasAttachments() );
		self::assertEquals( FALSE, $message->hasInlineImages() );
		self::assertEquals( FALSE, $message->hasMails() );

		self::assertEquals( 'Test', $message->getSubject() );

		$headers	= $message->getHeaders();

	}

	/**
	 *	@covers		::parse
	 *	@covers		::getInstance
	 *	@covers		::parseAtomicBodyPart
	 *	@covers		::parseMultipartBody
	 *	@covers		::createTextPart
	 *	@covers		::createHTMLPart
	 */
	public function testParse2(): void
	{
		$raw		= file_get_contents(__DIR__ . '/parserMailMultipart-plain,html,attachment,image.eml');
		$parser		= Parser::getInstance();
		$message	= $parser->parse( $raw );

		self::assertEquals( TRUE, $message->hasHTML() );
		self::assertEquals( TRUE, $message->hasText() );
		self::assertEquals( TRUE, $message->hasAttachments() );
		self::assertEquals( TRUE, $message->hasInlineImages() );
		self::assertEquals( FALSE, $message->hasMails() );

		self::assertEquals( 1, count( $message->getAttachments() ) );
		self::assertEquals( 1, count( $message->getInlineImages() ) );

		self::assertEquals( 'Test - 2022-02-19 17:41:23', $message->getSubject() );

		/** @var Attachment $attachment */
		$attachment	= current( $message->getAttachments() );
		self::assertEquals( 'README.md', $attachment->getFileName() );
		self::assertEquals( '3975', $attachment->getFileSize() );
		self::assertEquals( 'text/markdown', $attachment->getMimeType() );

		/** @var InlineImage $image */
		$image		= current( $message->getInlineImages() );
		self::assertEquals( 'test.png', $image->getFileName() );
		self::assertEquals( '50326', $image->getFileSize() );
		self::assertEquals( 'image/png', $image->getMimeType() );

		$headers	= $message->getHeaders();
	}
	/**
	 *	@covers		::parse
	 *	@covers		::getInstance
	 *	@covers		::parseAtomicBodyPart
	 *	@covers		::parseMultipartBody
	 *	@covers		::createTextPart
	 *	@covers		::createHTMLPart
	 */
	public function testParse3(): void
	{
		$raw		= file_get_contents(__DIR__ . '/parserMailSingle-faulty-microsoft-dmarc-report.eml');
		$parser		= Parser::getInstance();
		$message	= $parser->parse( $raw );

		self::assertEquals( TRUE, $message->hasHTML() );
		self::assertEquals( FALSE, $message->hasText() );
		self::assertEquals( TRUE, $message->hasAttachments() );
		self::assertEquals( FALSE, $message->hasInlineImages() );
		self::assertEquals( FALSE, $message->hasMails() );

		self::assertEquals( 1, count( $message->getAttachments() ) );

		self::assertEquals( 'Report Domain: mail.itflow.de Submitter: protection.outlook.com Report-ID: 411f976a0bd94ca48c79e350fa9e4ccd', $message->getSubject() );

		/** @var Attachment $attachment */
		$attachment	= current( $message->getAttachments() );
		self::assertEquals( 'protection.outlook.com!mail.itflow.de!1739750400!1739836800.xml.gz', $attachment->getFileName() );
		self::assertEquals( 'application/gzip', $attachment->getMimeType() );
	}

	
	/**
	 *	@covers		::parse
	 *	@covers		::getInstance
	 *	@covers		::parseAtomicBodyPart
	 *	@covers		::parseMultipartBody
	 *	@covers		::createTextPart
	 *	@covers		::createHTMLPart
	 *	@covers		::createAttachmentPart
	 *	@covers		::createDispositionPart
	 */
	public function testParseWithAttachment(): void
	{
		$raw		= file_get_contents(__DIR__ . '/parserMailMultipart-plain,html,attachment.eml');
		$message	= Parser::getInstance()->parse( $raw );

		self::assertEquals( TRUE, $message->hasHTML() );
		self::assertEquals( TRUE, $message->hasText() );
		self::assertEquals( TRUE, $message->hasAttachments() );
		self::assertEquals( FALSE, $message->hasInlineImages() );
		self::assertEquals( FALSE, $message->hasMails() );

		$address	= '"Christian Würker" <christian.wuerker@ceusmedia.de>';
		$recipient	= new Address( $address );

		$collectionCc = $message->getRecipientsByType( 'cc' );
		self::assertEquals( 1, count( $collectionCc ) );
		self::assertEquals( $recipient, $collectionCc->getAll()[0] );
		self::assertEquals( $recipient->get(), $collectionCc->getAll()[0]->get() );
		self::assertEquals( $address, $collectionCc->getAll()[0]->get() );

/*		$collection	= new AddressCollection( [$recipient] );
		print_m($message->getRecipientsByType( 'cc' )->getAll()[0]->get());die;
		print($message->getRecipientsByType( 'cc' )[0]->render());die;
		self::assertEquals( $collection,  );
		self::assertEquals( $collection, $message->getRecipientsByType( 'bcc' ) );*/
	}
}
