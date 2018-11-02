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
class Message_Header_SectionTest extends PHPUnit_Framework_TestCase
{
	public function testAddField(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->addField( $field );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->addField( $field );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	public function testAddFieldPair(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->addFieldPair( "key", "value" );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->addFieldPair( "key", "value" );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	public function testAddFields(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->addFields( array( $field, $field ) );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	public function testGetField(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->addField( $field );
		$this->assertEquals( $field, $section->getField( "key" ) );
		$this->assertEquals( $field, $section->getField( "KEY" ) );
	}

	public function testGetFieldException(){
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$this->setExpectedException( 'RangeException' );
		$this->assertEquals( NULL, $section->getField( "invalid" ) );
	}

	public function testGetFields(){
		$field1		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$field2		= new \CeusMedia\Mail\Message\Header\Field( "kEY", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$this->assertEquals( array(), $section->getFields() );
		$section->addFields( array( $field1, $field1, $field2, $field2 ) );
		$this->assertEquals( array( $field1, $field1, $field2, $field2 ), $section->getFields() );
	}

	public function testGetFieldsByName(){
		$field1		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$field2		= new \CeusMedia\Mail\Message\Header\Field( "kEY", "value" );
		$field3		= new \CeusMedia\Mail\Message\Header\Field( "ABC", "DEF" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->addFields( array( $field1, $field1, $field2, $field2, $field3 ) );
		$this->assertEquals( array( $field1, $field1, $field2, $field2 ), $section->getFieldsByName( "key" ) );
		$this->assertEquals( array( $field1, $field1, $field2, $field2 ), $section->getFieldsByName( "KEY" ) );
		$this->assertEquals( array(), $section->getFieldsByName( "invalid" ) );
	}

	public function testHasField(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$this->assertEquals( FALSE, $section->hasField( "key" ) );
		$section->addField( $field );
		$this->assertEquals( TRUE, $section->hasField( "key" ) );
		$this->assertEquals( TRUE, $section->hasField( "KEY" ) );
		$section->removeFieldByName( "key" );
		$this->assertEquals( FALSE, $section->hasField( "key" ) );
	}

	public function testRemoveByName(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();

		$section->addField( $field );
		$section->removeFieldByName( "key" );
		$this->assertEquals( array(), $section->getFields() );

		$section->addField( $field );
		$section->removeFieldByName( "KEY" );
		$this->assertEquals( array(), $section->getFields() );
	}

	public function testSetField(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->setField( $field );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setField( $field );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setField( $field, FALSE );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	public function testSetFieldPair(){
		$field		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->setFieldPair( "key", "value" );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setFieldPair( "key", "value" );
		$this->assertEquals( array( $field ), $section->getFields() );
		$section->setFieldPair( "key", "value", FALSE );
		$this->assertEquals( array( $field, $field ), $section->getFields() );
	}

	public function testSetFields(){
		$field1		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$field2		= new \CeusMedia\Mail\Message\Header\Field( "kEY", "vALUE" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$section->setFields( array( $field1, $field1, $field2 ) );
		$this->assertEquals( array( $field2 ), $section->getFields() );
		$section->setFields( array( $field1, $field1, $field2 ) );
		$this->assertEquals( array( $field2 ), $section->getFields() );

		$section->setFields( array( $field1, $field2 ), FALSE );
		$this->assertEquals( array( $field2, $field1, $field2 ), $section->getFields() );
	}

	public function testToString(){
		$delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$field1		= new \CeusMedia\Mail\Message\Header\Field( "key", "value" );
		$field2		= new \CeusMedia\Mail\Message\Header\Field( "kEY", "vALUE" );
		$section	= new \CeusMedia\Mail\Message\Header\Section();
		$this->assertEquals( '', $section->toString() );

		$section->addFields( array( $field1, $field1, $field2 ) );
		$assertion	= "Key: value".$delimiter."Key: value".$delimiter."Key: vALUE";
		$this->assertEquals( $assertion, $section->toString() );

		$section->setFields( array( $field1, $field1, $field2 ) );
		$assertion	= "Key: vALUE";
		$this->assertEquals( $assertion, $section->toString() );
	}
}
