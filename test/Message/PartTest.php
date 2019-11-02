<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( __DIR__ ).'/bootstrap.php';

use CeusMedia\Mail\Message\Part;

/**
 *	Unit test for mail message_part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part
 */
class Message_PartTest extends TestCase
{
	/**
	 *	@covers		::getCharset
	 *	@covers		::setCharset
	 */
	public function testCharset(){
		$charset	= "whatYouWant";
		$part		= new Message_Part();
		$part->setCharset( $charset );
		$this->assertEquals( $charset, $part->getCharset() );
	}

	/**
	 *	@covers		::getContent
	 *	@covers		::setContent
	 */
	public function testContent(){
		$content	= "whatYouWant";
		$part		= new Message_Part();
		$part->setContent( $content );
		$this->assertEquals( $content, $part->getContent() );
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
			$this->assertEquals( $encoding, $part->getEncoding() );
		}
	}

	/**
	 *	@covers		::setEncoding
	 */
	public function testEncodingException(){
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
			$this->assertEquals( $format, $part->getFormat() );
		}
	}

	/**
	 *	@covers		::setFormat
	 */
	public function testFormatException(){
		$this->expectException( 'InvalidArgumentException' );
		$part		= new Message_Part();
		$part->setFormat( 'invalid' );
	}

	/**
	 *	@covers		::getMimeType
	 *	@covers		::setMimeType
	 */
	public function testMimeType(){
		$mimeType	= "whatYouWant";
		$part		= new Message_Part();
		$part->setMimeType( $mimeType );
		$this->assertEquals( $mimeType, $part->getMimeType() );
	}
}
class Message_Part extends Part
{
	public function render( $headers = NULL ): string
	{
		return json_encode( $this );
	}
}
