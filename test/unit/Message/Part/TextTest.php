<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Part;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part;
use CeusMedia\Mail\Message\Part\Text;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\Text
 */
class TextTest extends TestCase
{
	protected $delimiter;
	protected $lineLength;

	public function setup(): void
	{
		$this->delimiter	= Message::$delimiter;
		$this->lineLength	= Message::$lineLength;
	}

	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$content	= "This is the content.";
		$part		= new Text( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "UTF-8", $part->getCharset() );
		$this->assertEquals( "text/plain", $part->getMimeType() );
		$this->assertEquals( "base64", $part->getEncoding() );
		$this->assertEquals( "fixed", $part->getFormat() );

		$part		= new Text( $content, 'latin1', 'base64' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "latin1", $part->getCharset() );
		$this->assertEquals( "base64", $part->getEncoding() );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender()
	{
		$content	= "This is the content with umlauts: äöü.";

		$part		= new Text( $content, 'utf-8' );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/plain; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: base64',
			'',
			$this->wrapContent( base64_encode( $content ) ),
		) );
		$this->assertEquals( $expected, $part->render() );

		$part		= new Text( $content, 'utf-8', 'quoted-printable' );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/plain; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: quoted-printable',
			'',
			$this->wrapContent( quoted_printable_encode( $content ) ),
		) );
		$this->assertEquals( $expected, $part->render() );

		$part		= new Text( $content );
		$part->setMimeType( "text/PLAIN" );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/PLAIN; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: base64',
			'',
			$this->wrapContent( base64_encode( $content ) ),
		) );
		$this->assertEquals( $expected, $part->render() );
	}

	protected function wrapContent( string $content ): string
	{
		return rtrim( chunk_split( $content, $this->lineLength, $this->delimiter ), $this->delimiter );
	}
}
