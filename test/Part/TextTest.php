<?php
/**
 *	Unit test for mail header field.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Part_TextTest extends PHPUnit_Framework_TestCase
{
	protected $delimiter;
	protected $lineLength;

	public function setUp(){
		$this->delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$this->lineLength	= \CeusMedia\Mail\Message::$lineLength;
	}

	public function testConstruct(){
		$content	= "This is the content.";
		$part		= new \CeusMedia\Mail\Part\Text( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "UTF-8", $part->getCharset() );
		$this->assertEquals( "text/plain", $part->getMimeType() );
		$this->assertEquals( "quoted-printable", $part->getEncoding() );
		$this->assertEquals( "fixed", $part->getFormat() );

		$part		= new \CeusMedia\Mail\Part\Text( $content, 'latin1', 'base64' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "latin1", $part->getCharset() );
		$this->assertEquals( "base64", $part->getEncoding() );
	}

	public function testRender(){
		$content	= "This is the content with umlauts: äöü.";

		$part		= new \CeusMedia\Mail\Part\Text( $content );
		$assertion	= 'Content-Type: text/plain;'.$this->delimiter.' charset="UTF-8"'.$this->delimiter.'Content-Transfer-Encoding: quoted-printable'.$this->delimiter.$this->delimiter.quoted_printable_encode( $content );
		$this->assertEquals( $assertion, $part->render() );

		$part		= new \CeusMedia\Mail\Part\Text( $content, 'utf-8', 'base64' );
		$part->setMimeType( "text/PLAIN" );
		$assertion	= 'Content-Type: text/PLAIN;'.$this->delimiter.' charset="utf-8"'.$this->delimiter.'Content-Transfer-Encoding: base64'.$this->delimiter.$this->delimiter.chunk_split( base64_encode( $content ), $this->lineLength );
		$this->assertEquals( $assertion, $part->render() );
	}
}