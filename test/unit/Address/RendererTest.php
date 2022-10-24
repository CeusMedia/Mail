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
	public function testGetInstance()
	{
		$instance	= Renderer::getInstance();
		$this->assertEquals( new Renderer(), $instance );
	}

	/**
	 *	@covers		::create
	 *	@covers		::getInstance
	 *	@covers		::render
	 */
	public function testRender()
	{
		$renderer	= new Renderer();
		$address	= Address::getInstance()
			->setDomain( 'muster-server.tld' )
			->setLocalPart( 'Hans.Mustermann' );
		$expected	= 'Hans.Mustermann@muster-server.tld';
		$this->assertEquals( $expected, $renderer->render( $address ) );

		$address->setName( 'Hans Mustermann' );
		$expected	= '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		$this->assertEquals( $expected, $renderer->render( $address ) );

		$address->setName( 'Hans_Mustermann' );
		$expected	= 'Hans_Mustermann <Hans.Mustermann@muster-server.tld>';
		$this->assertEquals( $expected, $renderer->render( $address ) );

		$address	= new Address( '<Hans.Mustermann@muster-server.tld>' );
		$expected	= 'Hans.Mustermann@muster-server.tld';
		$this->assertEquals( $expected, $renderer->render( $address ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderExceptionNoDomain()
	{
		$this->expectException( 'RuntimeException' );
		$renderer	= new Renderer();
		$address	= new Address();
		$renderer->render( $address );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderExceptionNoLocalPart()
	{
		$this->expectException( 'RuntimeException' );
		$renderer	= new Renderer();
		$address	= new Address();
		$address->setDomain( 'muster-server.tld' );
		$renderer->render( $address );
	}
}
