<?php
/**
 *	Unit test for mail header field.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';

use CeusMedia\Mail\Message\Header\Field;

/**
 *	Unit test for mail recipient address validation.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Field
 */
class Message_Header_FieldTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$header	= new Field( "key", "value" );
		$assertion	= true;
		$creation	= (bool) strlen( $header->toString() );
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::getName
	 */
	public function testName()
	{
		$header		= new Field( "Key-with-Value", "Value with Space" );
		$assertion	= "Key-with-Value";
		$this->assertEquals( $assertion, $header->getName() );

		$header		= new Field( "as-HTML", "Value with Space" );
		$assertion	= "as-HTML";
		$this->assertEquals( $assertion, $header->getName() );

		$header		= new Field( "as HTML", "Value with Space" );
		$assertion	= "as-HTML";
		$this->assertEquals( $assertion, $header->getName() );

		$header->setName( "key with spaces" );
		$assertion	= "key-with-spaces";
		$this->assertEquals( $assertion, $header->getName() );
	}

	/**
	 *	@covers		::getName
	 */
	public function testNameNotKeepCase()
	{
		$header		= new Field( "Key-with-Value", "Value with Space" );
		$assertion	= "Key-With-Value";
		$this->assertEquals( $assertion, $header->getName( FALSE ) );

		$header		= new Field( "as-HTML", "Value with Space" );
		$assertion	= "As-Html";
		$this->assertEquals( $assertion, $header->getName( FALSE ) );
	}

	/**
	 *	@covers		::getName
	 */
	public function testNameIgnoreMbConvert()
	{
		$header		= new Field( "as-HTML", "Value with Space" );
		$assertion	= "As-Html";
		$this->assertEquals( $assertion, $header->getName( FALSE, TRUE ) );

		$assertion	= "as-HTML";
		$this->assertEquals( $assertion, $header->getName( TRUE, TRUE ) );
	}

	/**
	 *	@covers		::getValue
	 */
	public function testValue()
	{
		$header		= new Field( "Key-with-Value", "Value with Space" );
		$assertion	= "Value with Space";
		$creation	= $header->getValue();
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToString()
	{
		$header		= new Field( "key", "value" );
		$assertion	= "key: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );

		$header		= new Field( "key-with-more-words", "value" );
		$assertion	= "key-with-more-words: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToStringByConversion()
	{
		$header		= new Field( "key", "value" );
		$assertion	= "key: value";
		$creation	= (string) $header;
		$this->assertEquals( $assertion, $creation );
	}
}
?>
