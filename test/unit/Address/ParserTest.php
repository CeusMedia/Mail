<?php
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Address;

use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
		$parser	= new \CeusMedia\Mail\Address\Parser();

		$expected	= 'Hans.Mustermann@muster-server.tld';
		$address	= $parser->parse( 'Hans.Mustermann@muster-server.tld' );
		$this->assertEquals( $expected, $address->get() );
		$address	= $parser->parse( '<Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( $expected, $address->get() );

		$expected	= '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		$address	= $parser->parse( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( $expected, $address->get() );

		$address	= $parser->parse( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' );
		$this->assertEquals( $expected, $address->get() );

		$expected	= 'Hans_Mustermann <Hans_Mustermann@muster-server.tld>';
		$address	= $parser->parse( 'Hans_Mustermann <Hans_Mustermann@muster-server.tld>' );
		$this->assertEquals( $expected, $address->get() );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParseException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser	= new \CeusMedia\Mail\Address\Parser();
		$parser->parse( 'invalid' );
	}
}
