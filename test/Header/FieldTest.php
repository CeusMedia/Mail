<?php
/**
 *	Unit test for mail header field.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Test_Header_FieldTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$header	= new \CeusMedia\Mail\Header\Field( "key", "value" );
		$assertion	= true;
		$creation	= (bool) count( $header->toString() );
		$this->assertEquals( $assertion, $creation );
	}

	public function testGetName()
	{
		$header	= new \CeusMedia\Mail\Header\Field( "Key-with-Value", "Value with Space" );

		$assertion	= "Key-With-Value";
		$creation	= $header->getName();
		$this->assertEquals( $assertion, $creation );
	}

	public function testGetValue()
	{
		$header	= new \CeusMedia\Mail\Header\Field( "Key-with-Value", "Value with Space" );

		$assertion	= "Value with Space";
		$creation	= $header->getValue();
		$this->assertEquals( $assertion, $creation );
	}

	public function testToString()
	{
		$header	= new \CeusMedia\Mail\Header\Field( "key", "value" );
		$assertion	= "Key: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );

		$header	= new \CeusMedia\Mail\Header\Field( "key", "value" );
		$assertion	= "Key: value";
		$creation	= (string) $header;
		$this->assertEquals( $assertion, $creation );

		$header	= new \CeusMedia\Mail\Header\Field( "key-with-more-words", "value" );
		$assertion	= "Key-With-More-Words: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );
	}
}
?>
