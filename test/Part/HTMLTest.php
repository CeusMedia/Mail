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
class Part_HTMLTest extends PHPUnit_Framework_TestCase
{
	protected $delimiter;
	protected $lineLength;

	public function setUp(){
		$this->delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$this->lineLength	= \CeusMedia\Mail\Message::$lineLength;
	}

	public function testConstruct(){
		$content	= "<b>This is the content.</b>";
		$part		= new \CeusMedia\Mail\Part\HTML( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "UTF-8", $part->getCharset() );
		$this->assertEquals( "text/html", $part->getMimeType() );
		$this->assertEquals( "quoted-printable", $part->getEncoding() );
		$this->assertEquals( "fixed", $part->getFormat() );

		$part		= new \CeusMedia\Mail\Part\HTML( $content, 'latin1', 'base64' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "latin1", $part->getCharset() );
		$this->assertEquals( "base64", $part->getEncoding() );
	}

	public function testRender(){
		$content	= "<b>This is the content with umlauts:</b> <em>äöü</em>.";

		$part		= new \CeusMedia\Mail\Part\HTML( $content );
		$assertion	= 'Content-Type: text/html;'.$this->delimiter.' charset="UTF-8"'.$this->delimiter.'Content-Transfer-Encoding: quoted-printable'.$this->delimiter.$this->delimiter.quoted_printable_encode( $content );
		$this->assertEquals( $assertion, $part->render() );

		$part		= new \CeusMedia\Mail\Part\HTML( $content, 'utf-8', 'base64' );
		$part->setMimeType( "text/xhtml" );
		$assertion	= 'Content-Type: text/xhtml;'.$this->delimiter.' charset="utf-8"'.$this->delimiter.'Content-Transfer-Encoding: base64'.$this->delimiter.$this->delimiter.chunk_split( base64_encode( $content ), $this->lineLength );
		$this->assertEquals( $assertion, $part->render() );
	}
}
