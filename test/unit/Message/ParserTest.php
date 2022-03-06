<?php
/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message;

use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
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
		$raw		= file_get_contents( __DIR__.'/parserMailMultipart-plain,html.eml' );
		$parser		= Parser::getInstance();
		$message	= $parser->parse( $raw );

		$this->assertEquals( TRUE, $message->hasHTML() );
		$this->assertEquals( TRUE, $message->hasText() );
		$this->assertEquals( FALSE, $message->hasAttachments() );
		$this->assertEquals( FALSE, $message->hasInlineImages() );
		$this->assertEquals( FALSE, $message->hasMails() );

		$this->assertEquals( 'Test', $message->getSubject() );

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
		$raw		= file_get_contents( __DIR__.'/parserMailMultipart-plain,html,attachment.eml' );
		$message	= Parser::getInstance()->parse( $raw );

		$this->assertEquals( TRUE, $message->hasHTML() );
		$this->assertEquals( TRUE, $message->hasText() );
		$this->assertEquals( TRUE, $message->hasAttachments() );
		$this->assertEquals( FALSE, $message->hasInlineImages() );
		$this->assertEquals( FALSE, $message->hasMails() );

		$address	= '"Christian Würker" <christian.wuerker@ceusmedia.de>';
		$recipient	= new Address( $address );

		$collectionCc = $message->getRecipientsByType( 'cc' );
		$this->assertEquals( 1, count( $collectionCc ) );
		$this->assertEquals( $recipient, $collectionCc->getAll()[0] );
		$this->assertEquals( $recipient->get(), $collectionCc->getAll()[0]->get() );
		$this->assertEquals( $address, $collectionCc->getAll()[0]->get() );

/*		$collection	= new AddressCollection( [$recipient] );
		print_m($message->getRecipientsByType( 'cc' )->getAll()[0]->get());die;
		print($message->getRecipientsByType( 'cc' )[0]->render());die;
		$this->assertEquals( $collection,  );
		$this->assertEquals( $collection, $message->getRecipientsByType( 'bcc' ) );*/
	}
}
