<?php
/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\AttributedField;
use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Parser;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Header\\Parser' );

		$parser	= Parser::getInstance();
	}

	/**
	 *	@covers		::parseAttributedField
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedField()
	{
		$field	= new Field( 'key', 'value; key1="value1"; key2="value2"' );
		$attributedField	= Parser::parseAttributedField( $field );
		$this->assertEquals( 'key', $attributedField->getName() );
		$this->assertEquals( 'value', $attributedField->getValue() );
		$expected	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->assertEquals( $expected, $attributedField->getAttributes() );

		$field	= new Field( 'key', 'value; complex-key.1="Project Name \"Vanitas 2.0\""' );
		$attributedField	= Parser::parseAttributedField( $field );
		$this->assertEquals( 'key', $attributedField->getName() );
		$this->assertEquals( 'value', $attributedField->getValue() );
		$expected	= ['complex-key.1' => 'Project Name "Vanitas 2.0"'];
		$this->assertEquals( $expected, $attributedField->getAttributes() );

		$field	= new Field( 'Content-Disposition', 'form-data; name="field2"; filename="example.txt"' );
		$attributedField	= Parser::parseAttributedField( $field );
		$this->assertEquals( 'Content-Disposition', $attributedField->getName() );
		$this->assertEquals( 'form-data', $attributedField->getValue() );
		$expected	= ['name' => 'field2', 'filename' => 'example.txt'];
		$this->assertEquals( $expected, $attributedField->getAttributes() );

		$field	= new Field( 'X-Custom-Attributed', 'A custom value; first-attribute="Umlauts-Test #1 äöüÄÖÜß"; second-attribute="Quotes-Test \"a_b\""' );
		$attributedField	= Parser::parseAttributedField( $field );
		$this->assertEquals( 'X-Custom-Attributed', $attributedField->getName() );
		$this->assertEquals( 'A custom value', $attributedField->getValue() );
		$expected	= ['first-attribute' => 'Umlauts-Test #1 äöüÄÖÜß', 'second-attribute' => 'Quotes-Test "a_b"'];
		$this->assertEquals( $expected, $attributedField->getAttributes() );
	}

	/**
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedHeaderValue()
	{
		$string	= 'value; complex-key.1="Project Name \"Vanitas 2.0\""';
		$object	= Parser::parseAttributedHeaderValue( $string );
		$this->assertEquals( 'value', $object->getValue() );
		$expected	= ['complex-key.1' => 'Project Name "Vanitas 2.0"'];
		$this->assertEquals( $expected, $object->getAttributes() );
	}
}
