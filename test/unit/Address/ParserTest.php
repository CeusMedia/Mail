<?php
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Address;

use CeusMedia\Mail\Test\TestCase;
use CeusMedia\Mail\Address\Parser;

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
	 *	@codeCoverageIgnore
	 *	@covers		::create
	 */
/*	public function testCreate()
	{
		$this->markTestSkipped();
		if( version_compare( $this->version, '2.6' ) >= 0 ){
			$this->expectException();
			$this->expectExceptionMessageMatches( '/^Deprecated/' );

		}
		else if( version_compare( $this->version, '2.5' ) >= 0 ){
			$this->expectDeprecation();
			$this->expectDeprecationMessageMatches( '/^Deprecated/' );
		}
		$parser	= Parser::create();
	}*/

	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance()
	{
		$instance	= Parser::getInstance();
		$this->assertEquals( new Parser(), $instance );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
		$parser	= new Parser();

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
		$parser	= new Parser();
		$parser->parse( 'invalid' );
	}
}
