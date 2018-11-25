<?php
/**
 *	Unit test for mail header field.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Message_Header_FieldTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$header	= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$assertion	= true;
		$creation	= (bool) strlen( $header->toString() );
		$this->assertEquals( $assertion, $creation );
	}

	public function testName()
	{
		$header		= new \CeusMedia\Mail\Message\Header\Field( "Key-with-Value", "Value with Space" );
		$assertion	= "Key-with-Value";
		$this->assertEquals( $assertion, $header->getName() );

		$header		= new \CeusMedia\Mail\Message\Header\Field( "as-HTML", "Value with Space" );
		$assertion	= "as-HTML";
		$this->assertEquals( $assertion, $header->getName() );

		$header		= new \CeusMedia\Mail\Message\Header\Field( "as HTML", "Value with Space" );
		$assertion	= "as-HTML";
		$this->assertEquals( $assertion, $header->getName() );

		$header->setName( "key with spaces" );
		$assertion	= "key-with-spaces";
		$this->assertEquals( $assertion, $header->getName() );
	}

	public function testNameNotKeepCase()
	{
		$header		= new \CeusMedia\Mail\Message\Header\Field( "Key-with-Value", "Value with Space" );
		$assertion	= "Key-With-Value";
		$this->assertEquals( $assertion, $header->getName( FALSE ) );

		$header		= new \CeusMedia\Mail\Message\Header\Field( "as-HTML", "Value with Space" );
		$assertion	= "As-Html";
		$this->assertEquals( $assertion, $header->getName( FALSE ) );
	}


	public function testNameIgnoreMbConvert()
	{
		$header		= new \CeusMedia\Mail\Message\Header\Field( "as-HTML", "Value with Space" );
		$assertion	= "As-Html";
		$this->assertEquals( $assertion, $header->getName( FALSE, TRUE ) );

		$assertion	= "as-HTML";
		$this->assertEquals( $assertion, $header->getName( TRUE, TRUE ) );
	}

	public function testValue()
	{
		$header		= new \CeusMedia\Mail\Message\Header\Field( "Key-with-Value", "Value with Space" );
		$assertion	= "Value with Space";
		$creation	= $header->getValue();
		$this->assertEquals( $assertion, $creation );
	}

	public function testToString()
	{
		$header		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$assertion	= "key: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );

		$header		= new \CeusMedia\Mail\Message\Header\Field( "key-with-more-words", "value" );
		$assertion	= "key-with-more-words: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );
	}

	public function testToStringByConversion()
	{
		$header		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$assertion	= "key: value";
		$creation	= (string) $header;
		$this->assertEquals( $assertion, $creation );
	}
}
?>
