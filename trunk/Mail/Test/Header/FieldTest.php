<?php
/**
 *	UnitTest for Request Header Field.
 *	@package		net.http.request
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.02.2008
 *	@version		0.6
 */
/**
 *	UnitTest for Request Header Field.
 *	@package		net.http.request
 *	@uses			Net_HTTP_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.02.2008
 *	@version		0.6
 */
class Test_Header_FieldTest extends Test_Case
{
	public function testConstruct()
	{
		$header	= new CMM_Mail_Header_Field( "key", "value" );
		$assertion	= true;
		$creation	= (bool) count( $header->toString() );
		$this->assertEquals( $assertion, $creation );
	}

	public function testGetName()
	{
		$header	= new CMM_Mail_Header_Field( "Key-with-Value", "Value with Space" );

		$assertion	= "Key-With-Value";
		$creation	= $header->getName();
		$this->assertEquals( $assertion, $creation );
	}

	public function testGetValue()
	{
		$header	= new CMM_Mail_Header_Field( "Key-with-Value", "Value with Space" );

		$assertion	= "Value with Space";
		$creation	= $header->getValue();
		$this->assertEquals( $assertion, $creation );
	}

	public function testToString()
	{
		$header	= new CMM_Mail_Header_Field( "key", "value" );
		$assertion	= "Key: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );

		$header	= new CMM_Mail_Header_Field( "key", "value" );
		$assertion	= "Key: value";
		$creation	= (string) $header;
		$this->assertEquals( $assertion, $creation );

		$header	= new CMM_Mail_Header_Field( "key-with-more-words", "value" );
		$assertion	= "Key-With-More-Words: value";
		$creation	= $header->toString();
		$this->assertEquals( $assertion, $creation );
	}
}
?>
