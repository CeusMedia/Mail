<?php
/**
 *	Unit test for mail header field.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once __DIR__.'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Part extends \CeusMedia\Mail\Part
{
	public function render(){
		return json_encode( $this );
	}
}
class PartTest extends PHPUnit_Framework_TestCase
{
	public function testCharset(){
		$charset	= "whatYouWant";
		$part		= new Part();
		$part->setCharset( $charset );
		$this->assertEquals( $charset, $part->getCharset() );
	}

	public function testContent(){
		$content	= "whatYouWant";
		$part		= new Part();
		$part->setContent( $content );
		$this->assertEquals( $content, $part->getContent() );
	}

	public function testEncoding()
	{
		$encodings	= array( "7bit", "8bit", "base64", "quoted-printable", "binary" );
		foreach( $encodings as $encoding ){
			$part		= new Part();
			$part->setEncoding( $encoding );
			$this->assertEquals( $encoding, $part->getEncoding() );
		}
	}

	/**
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testEncodingException(){
		$part		= new Part();
		$part->setEncoding( 'invalid' );
	}

	public function testFormat()
	{
		$formats	= array( "fixed", "flowed" );
		foreach( $formats as $format ){
			$part		= new Part();
			$part->setFormat( $format );
			$this->assertEquals( $format, $part->getFormat() );
		}
	}

	/**
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testFormatException(){
		$part		= new Part();
		$part->setFormat( 'invalid' );
	}

	public function testMimeType(){
		$mimeType	= "whatYouWant";
		$part		= new Part();
		$part->setMimeType( $mimeType );
		$this->assertEquals( $mimeType, $part->getMimeType() );
	}
}
