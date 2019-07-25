<?php
/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Collection as AddressCollection;
use \CeusMedia\Mail\Address\Collection\Renderer;

/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection\Renderer
 */
class Address_Collection_RendererTest extends TestCase
{
	protected $renderer;

	public function __construct(){
		$this->renderer	= new Renderer();
		parent::__construct();
	}

	/**
	 *	@covers		::render
	 */
	public function testRender1(){
		$collection		= new AddressCollection( array(
			new Address( 'dev@ceusmedia.de' ),
		) );
		$expected	= 'dev@ceusmedia.de';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender2(){
		$collection		= new AddressCollection( array(
			new Address( '<dev@ceusmedia.de>' ),
		) );

		$expected	= 'dev@ceusmedia.de';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender3(){
		$collection		= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
		) );

		$expected	= 'Developer <dev@ceusmedia.de>';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender4(){
		$collection		= new AddressCollection( array(
			new Address( '"Developer" <dev@ceusmedia.de>' ),
		) );

		$expected	= 'Developer <dev@ceusmedia.de>';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender5(){
		$collection		= new AddressCollection( array(
			new Address( '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>' ),
		) );
		$expected	= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender6(){
		$collection		= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
			new Address( 'Alpha Tester <test@ceusmedia.de>' ),
		) );
		$expected	= 'Developer <dev@ceusmedia.de>, "Alpha Tester" <test@ceusmedia.de>';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender7(){
		$collection		= new AddressCollection( array() );
		$expected	= '';
		$this->assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::setDelimiter
	 */
	public function testSetDelimiterException(){
		$this->expectException( 'InvalidArgumentException' );
		$this->renderer->setDelimiter( '' );
	}
}
