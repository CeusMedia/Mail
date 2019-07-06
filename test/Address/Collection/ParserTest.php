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
use \CeusMedia\Mail\Address\Collection\Parser;

/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection\Parser
 */
class Address_Collection_ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 */
	public function testParse(){
		$assertion	= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
			new Address( 'Tester <test@ceusmedia.de>' ),
		) );

		$string		= 'Developer <dev@ceusmedia.de>, Tester <test@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $assertion, $string );

		$string		= ',Developer <dev@ceusmedia.de>,  "Tester"  <test@ceusmedia.de>, ';
		$this->assertEqualsForAllMethods( $assertion, $string );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParseNameless(){
		$assertion	= new AddressCollection( array(
			new Address( 'dev@ceusmedia.de' ),
		) );
		$string		= 'dev@ceusmedia.de';
		$this->assertEqualsForAllMethods( $assertion, $string );

		$string		= ' dev@ceusmedia.de, ';
		$this->assertEqualsForAllMethods( $assertion, $string );

		$string		= '<dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $assertion, $string );

		$string		= ', <dev@ceusmedia.de> ,';
		$this->assertEqualsForAllMethods( $assertion, $string );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParseWithName(){
		$assertion	= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
		) );
		$string		= 'Developer <dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $assertion, $string );

		$string		= '"Developer" <dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $assertion, $string );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParseWithNameHavingComma(){
		$assertion	= new AddressCollection( array(
			new Address( 'Developer, Tester <dev@ceusmedia.de>' ),
		) );
		$string		= '"Developer, Tester" <dev@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $assertion, $string );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParseWithNameHavingSymbols(){
		$assertion	= new AddressCollection( array(
			new Address( 'Developer (Dev-Crew) <dev.dev-crew@ceusmedia.de>' ),
		) );
		$string		= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		$this->assertEqualsForAllMethods( $assertion, $string );
	}

	//  --  PROTECTED  --  //
	protected function assertEqualsForAllMethods( $assertion, $string ){
		$parser	= Parser::create();
		$parser->setMethod( Parser::METHOD_OWN );
		$this->assertEquals( $assertion, $parser->parse( $string ) );
		$parser->setMethod( Parser::METHOD_IMAP );
		$this->assertEquals( $assertion, $parser->parse( $string ) );
	}
}
