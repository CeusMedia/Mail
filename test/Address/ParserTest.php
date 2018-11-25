<?php
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Address_ParserTest extends PHPUnit_Framework_TestCase
{

	public function testParse(){
		$parser	= new \CeusMedia\Mail\Address\Parser();

		$assertion	= 'Hans.Mustermann@muster-server.tld';
		$address	= $parser->parse( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( $assertion, $address->get() );
		$address	= $parser->parse( '<Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( $assertion, $address->get() );

		$assertion	= '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		$address	= $parser->parse( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( $assertion, $address->get() );

		$address	= $parser->parse( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( $assertion, $address->get() );

		$assertion	= 'Hans_Mustermann <Hans_Mustermann@muster-server.tld>';
		$address	= $parser->parse( 'Hans_Mustermann <Hans_Mustermann@muster-server.tld>' );
		$this->assertEquals( $assertion, $address->get() );
	}


	/**
	 *	@expectedException		InvalidArgumentException
	 */
	public function testParseException(){
		$parser	= new \CeusMedia\Mail\Address\Parser();
		$parser->parse( 'invalid' );
	}
}
