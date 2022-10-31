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
