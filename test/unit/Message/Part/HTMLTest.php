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
use CeusMedia\Mail\Message\Part\HTML;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\HTML
 */
class HTMLTest extends TestCase
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
		$content	= "<b>This is the content.</b>";
		$part		= new HTML( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "UTF-8", $part->getCharset() );
		$this->assertEquals( "text/html", $part->getMimeType() );
		$this->assertEquals( "base64", $part->getEncoding() );
		$this->assertEquals( "fixed", $part->getFormat() );

		$part		= new HTML( $content, 'latin1', 'base64' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( "latin1", $part->getCharset() );
		$this->assertEquals( "base64", $part->getEncoding() );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender()
	{
		$content	= "<b>This is the content with umlauts:</b> <em>äöü</em>.";

		$part		= new HTML( $content, 'utf-8', 'quoted-printable' );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/html; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: quoted-printable',
			'',
			$this->wrapContent( quoted_printable_encode( $content ) ),
		) );
		$this->assertEquals( $expected, $part->render() );

		$part		= new HTML( $content, 'utf-8', 'base64' );
		$part->setMimeType( "text/xhtml" );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/xhtml; charset="utf-8"; format=fixed',
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
