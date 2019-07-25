<?php
/**
 *	Unit test for mail address parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( __DIR__ ).'/bootstrap.php';

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Renderer;

/**
 *	Unit test for mail address renderer.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Renderer
 */
class Address_RendererTest extends TestCase
{

	/**
	 *	@covers		::render
	 */
	public function testRender(){
		$renderer	= new Renderer();
		$address	= Address::create()
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
	public function testRenderExceptionNoDomain(){
		$this->expectException( 'RuntimeException' );
		$renderer	= new Renderer();
		$address	= new Address();
		$renderer->render( $address );
	}

	/**
	 *	@covers		::render
	 */
	public function testRenderExceptionNoLocalPart(){
		$this->expectException( 'RuntimeException' );
		$renderer	= new Renderer();
		$address	= new Address();
		$address->setDomain( 'muster-server.tld' );
		$renderer->render( $address );
	}
}
