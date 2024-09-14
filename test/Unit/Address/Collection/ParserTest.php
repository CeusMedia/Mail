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
	public function testGetInstance(): void
	{
		$parser		= new Parser();
		$instance	= Parser::getInstance();

		self::assertEquals( $parser, $instance );
	}

	/**
	 *	@covers		::parse
	 *	@covers		::parseUsingImap
	 *	@covers		::parseUsingOwn
	 *	@covers		::realizeParserMethod
	 */
	public function testParse(): void
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
	public function testParseNameless(): void
	{
		$expected	= new AddressCollection( [new Address( 'dev@ceusmedia.de' )] );
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
	public function testParseWithName(): void
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
	public function testParseWithNameHavingComma(): void
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
	public function testParseWithNameHavingSymbols(): void
	{
		$expected	= new AddressCollection( array(
			new Address( 'Developer (Dev-Crew) <dev.dev-crew@ceusmedia.de>' ),
		) );
		$string		= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $expected, $string );
	}

	public function testParseUsingOwn(): void
	{
		$parser		= Parser::getInstance();
		$actual		= $parser->parseUsingOwn( '', ',' );
		$expected	= new AddressCollection();
		$this->assertEqualsForAllMethods( $expected, $actual );
	}

	public function testParseUsingOwnException1(): void
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser		= Parser::getInstance()->parseUsingOwn( 'a', '' );
	}

	public function testParseUsingOwnException2(): void
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser		= Parser::getInstance()->parseUsingOwn( 'a', ' ' );
	}

	/**
	 *	@covers		::setMethod
	 *	@covers		::getMethod
	 */
	public function testSetMethod(): void
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
			self::assertEquals( $method, $parser->getMethod() );
		}
	}

	/**
	 *	@covers		::setMethod
	 */
	public function testSetMethodException(): void
	{
		$this->expectException( 'InvalidArgumentException' );
		$parser	= Parser::getInstance()->setMethod( -1 );
	}

	//  --  PROTECTED  --  //
	protected function assertEqualsForAllMethods( Address\Collection $expected, string $string ): void
	{
		$parser	= Parser::getInstance();
		$parser->setMethod( Parser::METHOD_OWN );
		self::assertEquals( $expected, $parser->parse( $string ) );
		$parser->setMethod( Parser::METHOD_IMAP );
		self::assertEquals( $expected, $parser->parse( $string ) );
		$parser->setMethod( Parser::METHOD_IMAP_PLUS_OWN );
		self::assertEquals( $expected, $parser->parse( $string ) );
	}
}
