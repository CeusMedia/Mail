<?php
/**
 *	Unit test for mail header field.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Message\Header;

use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail recipient address validation.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
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
		$header	= new Field( "key", "value" );
		$expected	= true;
		$actual	= (bool) strlen( $header->toString() );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::getName
	 */
	public function testName()
	{
		$header		= new Field( "Key-with-Value", "Value with Space" );
		$expected	= "Key-with-Value";
		$this->assertEquals( $expected, $header->getName() );

		$header		= new Field( "as-HTML", "Value with Space" );
		$expected	= "as-HTML";
		$this->assertEquals( $expected, $header->getName() );

		$header		= new Field( "as HTML", "Value with Space" );
		$expected	= "as-HTML";
		$this->assertEquals( $expected, $header->getName() );

		$header->setName( "key with spaces" );
		$expected	= "key-with-spaces";
		$this->assertEquals( $expected, $header->getName() );
	}

	/**
	 *	@covers		::getName
	 */
	public function testNameNotKeepCase()
	{
		$header		= new Field( "Key-with-Value", "Value with Space" );
		$expected	= "Key-With-Value";
		$this->assertEquals( $expected, $header->getName( FALSE ) );

		$header		= new Field( "as-HTML", "Value with Space" );
		$expected	= "As-Html";
		$this->assertEquals( $expected, $header->getName( FALSE ) );
	}

	/**
	 *	@covers		::getName
	 */
	public function testNameIgnoreMbConvert()
	{
		$header		= new Field( "as-HTML", "Value with Space" );
		$expected	= "As-Html";
		$this->assertEquals( $expected, $header->getName( FALSE, TRUE ) );

		$expected	= "as-HTML";
		$this->assertEquals( $expected, $header->getName( TRUE, TRUE ) );
	}

	/**
	 *	@covers		::getValue
	 */
	public function testValue()
	{
		$header		= new Field( "Key-with-Value", "Value with Space" );
		$expected	= "Value with Space";
		$actual	= $header->getValue();
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToString()
	{
		$header		= new Field( "key", "value" );
		$expected	= "key: value";
		$actual	= $header->toString();
		$this->assertEquals( $expected, $actual );

		$header		= new Field( "key-with-more-words", "value" );
		$expected	= "key-with-more-words: value";
		$actual	= $header->toString();
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToStringByConversion()
	{
		$header		= new Field( "key", "value" );
		$expected	= "key: value";
		$actual	= (string) $header;
		$this->assertEquals( $expected, $actual );
	}
}
