<?php
/**
 *	Unit test for mail message part.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';
/**
 *	Unit test for mail message part.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Message_Part_TextTest extends PHPUnit_Framework_TestCase
{
	protected $delimiter;
	protected $lineLength;

	public function setUp(){
		$this->delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$this->lineLength	= \CeusMedia\Mail\Message::$lineLength;
	}

	public function testConstruct(){
		$content	= "This is the content.";
		$part		= new \CeusMedia\Mail\Message\Part\Text( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "UTF-8", $part->getCharset() );
		$this->assertEquals( "text/plain", $part->getMimeType() );
		$this->assertEquals( "base64", $part->getEncoding() );
		$this->assertEquals( "fixed", $part->getFormat() );

		$part		= new \CeusMedia\Mail\Message\Part\Text( $content, 'latin1', 'base64' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "latin1", $part->getCharset() );
		$this->assertEquals( "base64", $part->getEncoding() );
	}

	public function testRender(){
		$content	= "This is the content with umlauts: äöü.";

		$part		= new \CeusMedia\Mail\Message\Part\Text( $content, 'utf-8', 'quoted-printable' );
		$assertion	= 'Content-Type: text/plain;'.$this->delimiter.' charset="utf-8"'.$this->delimiter.'Content-Transfer-Encoding: quoted-printable'.$this->delimiter.$this->delimiter.quoted_printable_encode( $content );
		$this->assertEquals( $assertion, $part->render() );

		$part		= new \CeusMedia\Mail\Message\Part\Text( $content );
		$part->setMimeType( "text/PLAIN" );
		$assertion	= 'Content-Type: text/PLAIN;'.$this->delimiter.' charset="utf-8"'.$this->delimiter.'Content-Transfer-Encoding: base64'.$this->delimiter.$this->delimiter.chunk_split( base64_encode( $content ), $this->lineLength );
		$this->assertEquals( $assertion, $part->render() );
	}
}
