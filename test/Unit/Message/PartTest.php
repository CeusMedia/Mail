<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message;

use CeusMedia\Mail\Message\Part;
use CeusMedia\Mail\Message\Part\Attachment as AttachmentPart;
use CeusMedia\Mail\Message\Part\HTML as HtmlPart;
use CeusMedia\Mail\Message\Part\InlineImage as InlineImagePart;
use CeusMedia\Mail\Message\Part\Mail as MailPart;
use CeusMedia\Mail\Message\Part\Text as TextPart;
use CeusMedia\Mail\Message\Header\Section as HeaderSection;
use CeusMedia\MailTest\TestCase;


/**
 *	Unit test for mail message_part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part
 */
class PartTest extends TestCase
{
	/**
	 *	@covers		::getCharset
	 *	@covers		::setCharset
	 */
	public function testCharset()
	{
		$charset	= "whatYouWant";
		$part		= new Message_Part();
		$part->setCharset( $charset );
		self::assertEquals( $charset, $part->getCharset() );
	}

	/**
	 *	@covers		::getContent
	 *	@covers		::setContent
	 */
	public function testContent()
	{
		$content	= "whatYouWant";
		$part		= new Message_Part();
		$part->setContent( $content );
		self::assertEquals( $content, $part->getContent() );
	}

	/**
	 *	@covers		::getEncoding
	 *	@covers		::setEncoding
	 */
	public function testEncoding()
	{
		$encodings	= array( "7bit", "8bit", "base64", "quoted-printable", "binary" );
		foreach( $encodings as $encoding ){
			$part		= new Message_Part();
			$part->setEncoding( $encoding );
			self::assertEquals( $encoding, $part->getEncoding() );
		}
	}

	/**Transport_
	 *	@covers		::setEncoding
	 */
	public function testEncodingException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$part		= new Message_Part();
		$part->setEncoding( 'invalid' );
	}

	/**
	 *	@covers		::getFormat
	 *	@covers		::setFormat
	 */
	public function testFormat()
	{
		$formats	= array( "fixed", "flowed" );
		foreach( $formats as $format ){
			$part		= new Message_Part();
			$part->setFormat( $format );
			self::assertEquals( $format, $part->getFormat() );
		}
	}

	/**
	 *	@covers		::setFormat
	 */
	public function testFormatException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$part		= new Message_Part();
		$part->setFormat( 'invalid' );
	}

	/**
	 *	@covers		::getMimeType
	 *	@covers		::setMimeType
	 */
	public function testMimeType()
	{
		$mimeType	= "whatYouWant";
		$part		= new Message_Part();
		$part->setMimeType( $mimeType );
		self::assertEquals( $mimeType, $part->getMimeType() );
	}

	/**
	 *	@covers		::getType
	 */
	public function testGetType()
	{
		$parts	= [
			Part::TYPE_ATTACHMENT	=> new AttachmentPart(),
			Part::TYPE_INLINE_IMAGE	=> new InlineImagePart( 'test' ),
			Part::TYPE_HTML			=> new HtmlPart( 'test' ),
			Part::TYPE_TEXT			=> new TextPart( 'test' ),
			Part::TYPE_MAIL			=> new MailPart( 'test' ),
		];
		foreach( $parts as $type => $part ){
			self::assertEquals( $type, $part->getType() );
			self::assertEquals( $type, $part->getType( TRUE ) );
		}
	}

	/**
	 *	@covers		::isOfType
	 */
	public function testIsOfType()
	{
		$parts	= [
			Part::TYPE_ATTACHMENT	=> new AttachmentPart(),
			Part::TYPE_INLINE_IMAGE	=> new InlineImagePart( 'test' ),
			Part::TYPE_HTML			=> new HtmlPart( 'test' ),
			Part::TYPE_TEXT			=> new TextPart( 'test' ),
			Part::TYPE_MAIL			=> new MailPart( 'test' ),
		];
		foreach( $parts as $type => $part ){
			self::assertTrue( $part->isOfType( $type ) );
		}
	}

	/**
	 *	@covers		::isAttachment
	 */
	public function testIsAttachment()
	{
		$part	= new AttachmentPart();
		self::assertTrue( $part->isAttachment( $part ) );

		$parts	= [
			new HtmlPart( 'test' ),
			new InlineImagePart( 'test' ),
			new TextPart( 'test' ),
			new MailPart( 'test' ),
		];
		foreach( $parts as $part ){
			self::assertFalse( $part->isAttachment( $part ) );
		}
	}

	/**
	 *	@covers		::isInlineImage
	 */
	public function testIsInlineImage()
	{
		$part	= new InlineImagePart( 'test' );
		self::assertTrue( $part->isInlineImage( $part ) );

		$parts	= [
			new AttachmentPart(),
			new HtmlPart( 'test' ),
			new TextPart( 'test' ),
			new MailPart( 'test' ),
		];
		foreach( $parts as $part ){
			self::assertFalse( $part->isInlineImage( $part ) );
		}
	}

	/**
	 *	@covers		::isHTML
	 */
	public function testIsHTML()
	{
		$part	= new HtmlPart( 'test' );
		self::assertTrue( $part->isHTML( $part ) );

		$parts	= [
			new AttachmentPart(),
			new InlineImagePart( 'test' ),
			new TextPart( 'test' ),
			new MailPart( 'test' ),
		];
		foreach( $parts as $part ){
			self::assertFalse( $part->isHTML( $part ) );
		}
	}

	/**
	 *	@covers		::isMail
	 */
	public function testIsMail()
	{
		$part	= new MailPart( 'test' );
		self::assertTrue( $part->isMail( $part ) );

		$parts	= [
			new AttachmentPart(),
			new InlineImagePart( 'test' ),
			new HtmlPart( 'test' ),
			new TextPart( 'test' ),
		];
		foreach( $parts as $part ){
			self::assertFalse( $part->isMail( $part ) );
		}
	}

	/**
	 *	@covers		::isText
	 */
	public function testIsText()
	{
		$part	= new TextPart( 'test' );
		self::assertTrue( $part->isText( $part ) );

		$parts	= [
			new AttachmentPart(),
			new InlineImagePart( 'test' ),
			new HtmlPart( 'test' ),
			new MailPart( 'test' ),
		];
		foreach( $parts as $part ){
			self::assertFalse( $part->isText( $part ) );
		}
	}
}
class Message_Part extends Part
{
	public function render( int $sections = Part::SECTION_ALL, ?HeaderSection $additionalHeaders = NULL): string
	{
		return json_encode( $this );
	}
}
