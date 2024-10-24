<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Part;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part;
use CeusMedia\Mail\Message\Part\HTML;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\HTML
 */
class HTMLTest extends TestCase
{
	protected string $delimiter;
	protected int $lineLength;

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
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( "UTF-8", $part->getCharset() );
		self::assertEquals( "text/html", $part->getMimeType() );
		self::assertEquals( "base64", $part->getEncoding() );
		self::assertEquals( "fixed", $part->getFormat() );

		$part		= new HTML( $content, 'latin1', 'base64' );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( "latin1", $part->getCharset() );
		self::assertEquals( "base64", $part->getEncoding() );
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
		self::assertEquals( $expected, $part->render() );

		$part		= new HTML( $content, 'utf-8', 'base64' );
		$part->setMimeType( "text/xhtml" );
		$expected	= join( $this->delimiter, array(
			'Content-Type: text/xhtml; charset="utf-8"; format=fixed',
			'Content-Transfer-Encoding: base64',
			'',
			$this->wrapContent( base64_encode( $content ) ),
		) );
		self::assertEquals( $expected, $part->render() );
	}

	protected function wrapContent( string $content ): string
	{
		return rtrim( chunk_split( $content, $this->lineLength, $this->delimiter ), $this->delimiter );
	}
}
