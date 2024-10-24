<?php
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Address;

use CeusMedia\MailTest\TestCase;
use CeusMedia\Mail\Address\Parser;

/**
 *	Unit test for mail address parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address
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
		self::assertEquals( new Parser(), $instance );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
		$parser	= new Parser();

		$expected	= 'Hans.Mustermann@muster-server.tld';
		$address	= $parser->parse( 'Hans.Mustermann@muster-server.tld' );
		self::assertEquals( $expected, $address->get() );
		$address	= $parser->parse( '<Hans.Mustermann@muster-server.tld>' );
		self::assertEquals( $expected, $address->get() );

		$expected	= '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>';
		$address	= $parser->parse( 'Hans Mustermann <Hans.Mustermann@muster-server.tld>' );
		self::assertEquals( $expected, $address->get() );

		$address	= $parser->parse( '"Hans Mustermann" <Hans.Mustermann@muster-server.tld>' );
		self::assertEquals( $expected, $address->get() );

		$expected	= 'Hans_Mustermann <Hans_Mustermann@muster-server.tld>';
		$address	= $parser->parse( 'Hans_Mustermann <Hans_Mustermann@muster-server.tld>' );
		self::assertEquals( $expected, $address->get() );
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
