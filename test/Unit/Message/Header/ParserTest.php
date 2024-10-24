<?php
/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\AttributedField;
use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Parser;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 *	@todo		test giving strategies, too! (needs access to protected $strategy)
	 */
	public function testGetInstance()
	{
		$instance	= Parser::getInstance();
		self::assertEquals( new Parser(), $instance );
	}

	/**
	 *	@covers		::parse
	 */
/*	public function testParse()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Header\\Parser' );

		$parser	= Parser::getInstance();
	}*/

	/**
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedHeaderValue()
	{
		$string		= 'value; key1="value1"; key2="value2"';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'value', $object->getValue() );
		$expected	= ['key1' => 'value1', 'key2' => 'value2'];
		self::assertEquals( $expected, $object->getAttributes() );

		$string		= 'form-data; name="field2"; filename="example.txt"';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'form-data', $object->getValue() );
		$expected	= ['name' => 'field2', 'filename' => 'example.txt'];
		self::assertEquals( $expected, $object->getAttributes() );

		$string		= 'A custom value; first-attribute="Umlauts-Test #1 äöüÄÖÜß"; second-attribute="Quotes-Test \"a_b\""';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'A custom value', $object->getValue() );
		$expected	= ['first-attribute' => 'Umlauts-Test #1 äöüÄÖÜß', 'second-attribute' => 'Quotes-Test "a_b"'];
		self::assertEquals( $expected, $object->getAttributes() );

		$string		= 'value; complex-key.1="Project Name \"Vanitas 2.0\""';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'value', $object->getValue() );
		$expected	= ['complex-key.1' => 'Project Name "Vanitas 2.0"'];
		self::assertEquals( $expected, $object->getAttributes() );
	}
}
