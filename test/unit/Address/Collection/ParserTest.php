<?php
/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Address\Collection;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Address\Collection\Parser;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::create
	 *	@covers		::getInstance
	 *	@todo		remove coverage of create after removing method
	 */
	public function testGetInstance()
	{
		$parser		= new Parser();
		$instance	= Parser::getInstance();

		$this->assertEquals( $parser, $instance );
	}

	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 *	@covers		::realizeParserMethod
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
	 *	@covers		::realizeParserMethod
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
	 *	@covers		::realizeParserMethod
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
	 *	@covers		::realizeParserMethod
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
	 *	@covers		::realizeParserMethod
	 */
	public function testParseWithNameHavingSymbols()
	{
		$expected	= new AddressCollection( array(
			new Address( 'Developer (Dev-Crew) <dev.dev-crew@ceusmedia.de>' ),
		) );
		$string		= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	public function testParseUsingOwn()
	{
		$parser		= Parser::getInstance();
		$actual		= $parser->parseUsingOwn( '', ',' );
		$expected	= new AddressCollection();
		$this->assertEqualsForAllMethods( $expected, $actual );
	}

	public function testParseUsingOwnException1()
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser		= Parser::getInstance()->parseUsingOwn( 'a', '' );
	}

	public function testParseUsingOwnException2()
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser		= Parser::getInstance()->parseUsingOwn( 'a', ' ' );
	}

	/**
	 *	@covers		::setMethod
	 *	@covers		::getMethod
	 */
	public function testSetMethod()
	{
		$parser		= Parser::getInstance();
		$methods	= [
			Parser::METHOD_AUTO,
			Parser::METHOD_OWN,
			Parser::METHOD_IMAP,
			Parser::METHOD_IMAP_PLUS_OWN,
		];
		foreach( $methods as $method ){
			$parser->setMethod( $method );
			$this->assertEquals( $method, $parser->getMethod() );
		}
	}

	/**
	 *	@covers		::setMethod
	 */
	public function testSetMethodException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser	= Parser::getInstance()->setMethod( -1 );
	}

	//  --  PROTECTED  --  //
	protected function assertEqualsForAllMethods( $expected, $string )
	{
		$parser	= Parser::getInstance();
		$parser->setMethod( Parser::METHOD_OWN );
		$this->assertEquals( $expected, $parser->parse( $string ) );
		$parser->setMethod( Parser::METHOD_IMAP );
		$this->assertEquals( $expected, $parser->parse( $string ) );
		$parser->setMethod( Parser::METHOD_IMAP_PLUS_OWN );
		$this->assertEquals( $expected, $parser->parse( $string ) );
	}
}
