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
	public function testParse()
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
	 *	@covers		::createAttachmentPart
	 *	@covers		::createDispositionPart
	 */
	public function testParseWithAttachment()
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
