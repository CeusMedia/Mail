<?php
/**
 *	Unit test for mail header field.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\AttributedField;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail recipient address validation.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\AttributedField
 */
class AttributedFieldTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 *	@covers		::getAttributes
	 */
	public function testConstruct()
	{
		$field	= new AttributedField( 'key', 'value' );
		$this->assertEquals( [], $field->getAttributes() );

		$attributes	= ['key1' => 'value1', 'key2' => 'value2'];
		$field	= new AttributedField( 'key', 'value', $attributes );
		$this->assertEquals( 'key', $field->getName() );
		$this->assertEquals( 'value', $field->getValue() );
		$this->assertEquals( $attributes, $field->getAttributes() );
	}

	/**
	 *	@covers		::getAttribute
	 */
	public function testGetAttribute()
	{
		$attributes	= ['key1' => 'value1', 'key2' => 'value2'];
		$field	= new AttributedField( 'key', 'value', $attributes );
		$this->assertEquals( 'value1', $field->getAttribute( 'key1' ) );
		$this->assertEquals( 'value2', $field->getAttribute( 'key2' ) );
		$this->assertEquals( NULL, $field->getAttribute( 'key3' ) );
		$this->assertEquals( 'invalid', $field->getAttribute( 'key3', 'invalid' ) );
	}

	/**
	 *	@covers		::getAttribute
	 *	@covers		::setAttribute
	 *	@covers		::setAttributes
	 */
	public function testSetAttribute()
	{
		$field	= new AttributedField( 'key', 'value' );
		$field->setAttribute( 'key1', 'value1' );
		$this->assertEquals( 'value1', $field->getAttribute( 'key1' ) );

		$field->setAttribute( 'key2', 'value2' );
		$this->assertEquals( 'value2', $field->getAttribute( 'key2' ) );

		$field->setAttribute( 'key1', 'value3' );
		$this->assertEquals( 'value3', $field->getAttribute( 'key1' ) );
		$this->assertEquals( 'value2', $field->getAttribute( 'key2' ) );

		$field->setAttributes( [] );
		$this->assertEquals( NULL, $field->getAttribute( 'key1' ) );
		$this->assertEquals( NULL, $field->getAttribute( 'key2' ) );

		$field->setAttributes( ['key1' => 'value1', 'key2' => 'value2'] );
		$this->assertEquals( 'value1', $field->getAttribute( 'key1' ) );
		$this->assertEquals( 'value2', $field->getAttribute( 'key2' ) );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToString()
	{
		$field	= new AttributedField( 'key', 'value' );
		$this->assertEquals( 'key: value', $field->toString() );

		$attributes	= ['key1' => 'value1', 'key2' => 'value2'];
		$field	= new AttributedField( 'key', 'value', $attributes );
		$this->assertEquals( 'key: value; key1="value1"; key2="value2"', $field->toString() );

		$attributes	= ['complex-key.1' => 'Project Name "Vanitas 2.0"'];
		$field->setAttributes( $attributes );
		$this->assertEquals( 'key: value; complex-key.1="Project Name \"Vanitas 2.0\""', $field->toString() );
	}
}
