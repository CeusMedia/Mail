<?php
/**
 *	Unit test for mail header field.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail recipient address validation.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Field
 */
class FieldTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$header	= new Field( 'key', 'value' );
		$expected	= true;
		$actual	= (bool) strlen( $header->toString() );
		self::assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::getName
	 *	@covers		::setName
	 */
	public function testName()
	{
		$header		= new Field( 'Key-with-Value', 'Value with Space' );
		$expected	= 'Key-with-Value';
		self::assertEquals( $expected, $header->getName() );

		$header		= new Field( 'as-HTML', 'Value with Space' );
		$expected	= 'as-HTML';
		self::assertEquals( $expected, $header->getName() );

		$header		= new Field( 'as HTML', 'Value with Space' );
		$expected	= 'as-HTML';
		self::assertEquals( $expected, $header->getName() );

		$header->setName( 'key with spaces' );
		$expected	= 'key-with-spaces';
		self::assertEquals( $expected, $header->getName() );
	}

	/**
	 *	@covers		::setName
	 */
	public function testSetNameExceptionEmpty()
	{
		$this->expectException( 'InvalidArgumentException' );
		$field	= new Field( 'Key-with-Value', 'Value with Space' );
		$field->setName( '' );
	}

	/**
	 *	@covers		::setName
	 */
	public function testSetNameExceptionEmptyOnlySpace()
	{
		$this->expectException( 'InvalidArgumentException' );
		$field	= new Field( 'Key-with-Value', 'Value with Space' );
		$field->setName( '  ' );
	}

	/**
	 *	@covers		::getName
	 */
	public function testNameNotKeepCase()
	{
		$header		= new Field( 'Key-with-Value', 'Value with Space' );
		$expected	= 'Key-With-Value';
		self::assertEquals( $expected, $header->getName( FALSE ) );

		$header		= new Field( 'as-HTML', 'Value with Space' );
		$expected	= 'As-Html';
		self::assertEquals( $expected, $header->getName( FALSE ) );
	}

	/**
	 *	@covers		::getName
	 */
	public function testNameIgnoreMbConvert()
	{
		$header		= new Field( 'as-HTML', 'Value with Space' );
		$expected	= 'As-Html';
		self::assertEquals( $expected, $header->getName( FALSE, TRUE ) );

		$expected	= 'as-HTML';
		self::assertEquals( $expected, $header->getName( TRUE, TRUE ) );
	}

	/**
	 *	@covers		::getValue
	 *	@covers		::setValue
	 */
	public function testValue()
	{
		$header		= new Field( 'Key-with-Value', 'Value with Space' );
		$expected	= 'Value with Space';
		self::assertEquals( $expected, $header->getValue() );

		$expected	= 'New Value, with space and "quote"';
		$header->setValue( $expected );
		self::assertEquals( $expected, $header->getValue() );
	}

	/**
	 *	@covers		::__toString
	 *	@covers		::toString
	 */
	public function testToString()
	{
		$header		= new Field( 'key', 'value' );
		$expected	= 'key: value';
		self::assertEquals( $expected, $header->toString() );

		$header		= new Field( 'key-with-more-words', 'value' );
		$expected	= 'key-with-more-words: value';
		self::assertEquals( $expected, $header->toString() );

		self::assertEquals( $expected, (string) $header );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToStringByConversion()
	{
		$header		= new Field( 'key', 'value' );
		$expected	= 'key: value';
		$actual	= (string) $header;
		self::assertEquals( $expected, $actual );
	}
}
