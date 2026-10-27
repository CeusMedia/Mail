<?php
/**
 *	Unit test for mail address parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Address;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Renderer;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address renderer.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Renderer
 */
class RendererTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance(): void
	{
		$instance	= Renderer::getInstance();
		self::assertEquals( new Renderer(), $instance );
	}

	/**
	 *	@covers		::create
	 *	@covers		::getInstance
	 *	@covers		::render
	 */
	public function testRender(): void
	{
		$renderer	= new Renderer();
		$address	= Address::getInstance()
			->setDomain( 'muster-server.tld' )
			->setLocalPart( 'Hans.Mustermann' );
		$expected	= 'Hans.Mustermann@muster-server.tld';
		self::assertEquals( $expected, $renderer->render( $address ) );

		$address->setName( 'Hans Mustermann' );
		$expected	= '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		self::assertEquals( $expected, $renderer->render( $address ) );

		$address->setName( 'Hans_Mustermann' );
		$expected	= 'Hans_Mustermann <Hans.Mustermann@muster-server.tld>';
		self::assertEquals( $expected, $renderer->render( $address ) );

		$address	= new Address( '<Hans.Mustermann@muster-server.tld>' );
		$expected	= 'Hans.Mustermann@muster-server.tld';
		self::assertEquals( $expected, $renderer->render( $address ) );
	}

	/**
	 *	Quotes and backslashes in names are escaped.
	 *	@covers		::render
	 */
	public function testRenderEscapesName(): void
	{
		$address	= new Address( 'max@example.org' );
		$address->setName( 'Max "Mo" Muster \\ Co' );
		$expected	= '"Max \\"Mo\\" Muster \\\\ Co" <max@example.org>';
		self::assertSame( $expected, ( new Renderer() )->render( $address ) );
		self::assertSame( 'Max "Mo" Muster \\ Co', ( new Address( $expected ) )->getName( FALSE ) );
	}

	/**
	 *	For headers: non-ASCII names encoded as a whole, words separated by spaces unquoted.
	 *	@covers		::render
	 *	@covers		::setEncodeName
	 *	@covers		::isEncodingName
	 */
	public function testRenderEncodedName(): void
	{
		$renderer	= ( new Renderer() )->setEncodeName();
		self::assertTrue( $renderer->isEncodingName() );

		$address	= new Address( 'one@example.org' );
		self::assertSame( 'one@example.org', $renderer->render( $address ) );
		self::assertSame( 'Max Muster <one@example.org>', $renderer->render( $address->setName( 'Max Muster' ) ) );
		self::assertSame( '"Doe, John" <one@example.org>', $renderer->render( $address->setName( 'Doe, John' ) ) );
		$expected	= '=?UTF-8?B?'.base64_encode( 'Doe, Jürgen' ).'?= <one@example.org>';
		self::assertSame( $expected, $renderer->render( $address->setName( 'Doe, Jürgen' ) ) );

		self::assertFalse( $renderer->setEncodeName( FALSE )->isEncodingName() );
		self::assertSame( '"Doe, Jürgen" <one@example.org>', $renderer->render( $address ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderExceptionNoDomain(): void
	{
		$this->expectException( 'RuntimeException' );
		$renderer	= new Renderer();
		$address	= new Address();
		$renderer->render( $address );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderExceptionNoLocalPart(): void
	{
		$this->expectException( 'RuntimeException' );
		$renderer	= new Renderer();
		$address	= new Address();
		$address->setDomain( 'muster-server.tld' );
		$renderer->render( $address );
	}
}
