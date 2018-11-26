<?php
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Renderer;

/**
 *	Unit test for mail address renderer.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Address_RendererTest extends PHPUnit_Framework_TestCase
{

	public function testRender(){
		$address	= Address::create()
			->setDomain( 'muster-server.tld' )
			->setLocalPart( 'Hans.Mustermann' );
		$assertion	= 'Hans.Mustermann@muster-server.tld';
		$this->assertEquals( $assertion, Renderer::render( $address ) );

		$address->setName( 'Hans Mustermann' );
		$assertion	= '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		$this->assertEquals( $assertion, Renderer::render( $address ) );

		$address->setName( 'Hans_Mustermann' );
		$assertion	= 'Hans_Mustermann <Hans.Mustermann@muster-server.tld>';
		$this->assertEquals( $assertion, Renderer::render( $address ) );

		$address	= new Address( '<Hans.Mustermann@muster-server.tld>' );
		$assertion	= 'Hans.Mustermann@muster-server.tld';
		$this->assertEquals( $assertion, Renderer::render( $address ) );
	}

	public function testRenderExceptionNoDomain(){
		$this->expectException( 'RuntimeException' );
		$address	= new Address();
		Renderer::render( $address );
	}

	public function testRenderExceptionNoLocalPart(){
		$this->expectException( 'RuntimeException' );
		$address	= new Address();
		$address->setDomain( 'muster-server.tld' );
		Renderer::render( $address );
	}
}
