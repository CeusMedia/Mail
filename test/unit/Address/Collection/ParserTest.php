<?php
/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Address\Collection;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Address\Collection\Parser;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 */
	public function testParse()
	{
		$expected	= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
			new Address( 'Tester <test@ceusmedia.de>' ),
		) );

		$string		= 'Developer <dev@ceusmedia.de>, Tester <test@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );

		$string		= ',Developer <dev@ceusmedia.de>,  "Tester"  <test@ceusmedia.de>, ';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 */
	public function testParseNameless()
	{
		$expected	= new AddressCollection( array(
			new Address( 'dev@ceusmedia.de' ),
		) );
		$string		= 'dev@ceusmedia.de';
		$this->assertEqualsForAllMethods( $expected, $string );

		$string		= ' dev@ceusmedia.de, ';
		$this->assertEqualsForAllMethods( $expected, $string );

		$string		= '<dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );

		$string		= ', <dev@ceusmedia.de> ,';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 */
	public function testParseWithName()
	{
		$expected	= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
		) );
		$string		= 'Developer <dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );

		$string		= '"Developer" <dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 */
	public function testParseWithNameHavingComma()
	{
		$expected	= new AddressCollection( array(
			new Address( 'Developer, Tester <dev@ceusmedia.de>' ),
		) );
		$string		= '"Developer, Tester" <dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 */
	public function testParseWithNameHavingSymbols()
	{
		$expected	= new AddressCollection( array(
			new Address( 'Developer (Dev-Crew) <dev.dev-crew@ceusmedia.de>' ),
		) );
		$string		= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	//  --  PROTECTED  --  //
	protected function assertEqualsForAllMethods( $expected, $string )
	{
		$parser	= Parser::getInstance();
		$parser->setMethod( Parser::METHOD_OWN );
		$this->assertEquals( $expected, $parser->parse( $string ) );
		$parser->setMethod( Parser::METHOD_IMAP );
		$this->assertEquals( $expected, $parser->parse( $string ) );
	}
}
