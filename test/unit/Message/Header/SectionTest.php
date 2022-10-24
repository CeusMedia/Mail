<?php
/**
 *	Unit test for mail header field.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Section;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail recipient address validation.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Section
 */
class SectionTest extends TestCase
{
	/**
	 *	@covers		::addField
	 */
	public function testAddField()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$section->addField( $field );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->addField( $field );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	/**
	 *	@covers		::addFieldPair
	 */
	public function testAddFieldPair()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$section->addFieldPair( "key", "value" );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->addFieldPair( "key", "value" );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	/**
	 *	@covers		::addFields
	 */
	public function testAddFields()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$section->addFields( array( $field, $field ) );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	/**
	 *	@covers		::getField
	 */
	public function testGetField()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$section->addField( $field );
		$this->assertEquals( $field, $section->getField( "key" ) );
		$this->assertEquals( $field, $section->getField( "KEY" ) );
	}

	/**
	 *	@covers		::getField
	 */
	public function testGetFieldException()
	{
		$section	= new Section();
		$this->expectException( 'RangeException' );
		$this->assertEquals( NULL, $section->getField( "invalid" ) );
	}

	/**
	 *	@covers		::getFields
	 */
	public function testGetFields()
	{
		$field1		= new Field( "key", "value" );
		$field2		= new Field( "kEY", "value" );
		$section	= new Section();
		$this->assertEquals( array(), $section->getFields() );
		$section->addFields( array( $field1, $field1, $field2, $field2 ) );
		$this->assertEquals( array( $field1, $field1, $field2, $field2 ), $section->getFields() );
	}

	/**
	 *	@covers		::getFieldsByName
	 */
	public function testGetFieldsByName()
	{
		$field1		= new Field( "key", "value" );
		$field2		= new Field( "kEY", "value" );
		$field3		= new Field( "ABC", "DEF" );
		$section	= new Section();
		$section->addFields( array( $field1, $field1, $field2, $field2, $field3 ) );
		$this->assertEquals( array( $field1, $field1, $field2, $field2 ), $section->getFieldsByName( "key" ) );
		$this->assertEquals( array( $field1, $field1, $field2, $field2 ), $section->getFieldsByName( "KEY" ) );
		$this->assertEquals( array(), $section->getFieldsByName( "invalid" ) );
	}

	/**
	 *	@covers		::hasField
	 */
	public function testHasField()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$this->assertEquals( FALSE, $section->hasField( "key" ) );
		$section->addField( $field );
		$this->assertEquals( TRUE, $section->hasField( "key" ) );
		$this->assertEquals( TRUE, $section->hasField( "KEY" ) );
		$section->removeFieldByName( "key" );
		$this->assertEquals( FALSE, $section->hasField( "key" ) );
	}

	/**
	 *	@covers		::removeFieldByName
	 */
	public function testRemoveFieldByName()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();

		$section->addField( $field );
		$section->removeFieldByName( "key" );
		$this->assertEquals( array(), $section->getFields() );

		$section->addField( $field );
		$section->removeFieldByName( "KEY" );
		$this->assertEquals( array(), $section->getFields() );
	}

	/**
	 *	@covers		::setField
	 */
	public function testSetField()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$section->setField( $field );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setField( $field );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setField( $field, FALSE );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	/**
	 *	@covers		::setFieldPair
	 */
	public function testSetFieldPair()
	{
		$field		= new Field( "key", "value" );
		$section	= new Section();
		$section->setFieldPair( "key", "value" );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setFieldPair( "key", "value" );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setFieldPair( "key", "value", FALSE );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	/**
	 *	@covers		::setFields
	 */
	public function testSetFields()
	{
		$field1		= new Field( "key", "value" );
		$field2		= new Field( "kEY", "vALUE" );
		$section	= new Section();
		$section->setFields( array( $field1, $field1, $field2 ) );
		$this->assertEquals( array( $field2 ), $section->getFields() );
		$section->setFields( array( $field1, $field1, $field2 ) );
		$this->assertEquals( array( $field2 ), $section->getFields() );

		$section->setFields( array( $field1, $field2 ), FALSE );
		$this->assertEquals( array( $field2, $field1, $field2 ), $section->getFields() );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToString()
	{
		$delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$field1		= new Field( "key", "value" );
		$field2		= new Field( "kEY", "vALUE" );
		$section	= new Section();
		$this->assertEquals( '', $section->toString() );

		$section->addFields( array( $field1, $field1, $field2 ) );
		$expected	= "Key: value".$delimiter."Key: value".$delimiter."Key: vALUE";
		$this->assertEquals( $expected, $section->toString() );

		$section->setFields( array( $field1, $field1, $field2 ) );
		$expected	= "Key: vALUE";
		$this->assertEquals( $expected, $section->toString() );
	}
}
