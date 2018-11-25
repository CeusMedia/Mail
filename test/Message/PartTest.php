<?php
/**
 *	Unit test for mail message part.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail message_part.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Message_Part extends \CeusMedia\Mail\Message\Part
{
	public function render(){
		return json_encode( $this );
	}
}
class Message_PartTest extends PHPUnit_Framework_TestCase
{
	public function testCharset(){
		$charset	= "whatYouWant";
		$part		= new Message_Part();
		$part->setCharset( $charset );
		$this->assertEquals( $charset, $part->getCharset() );
	}

	public function testContent(){
		$content	= "whatYouWant";
		$part		= new Message_Part();
		$part->setContent( $content );
		$this->assertEquals( $content, $part->getContent() );
	}

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
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testEncodingException(){
		$part		= new Message_Part();
		$part->setEncoding( 'invalid' );
	}

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
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testFormatException(){
		$part		= new Message_Part();
		$part->setFormat( 'invalid' );
	}

	public function testMimeType(){
		$mimeType	= "whatYouWant";
		$part		= new Message_Part();
		$part->setMimeType( $mimeType );
		$this->assertEquals( $mimeType, $part->getMimeType() );
	}
}
