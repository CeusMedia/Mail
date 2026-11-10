<?php
/**
 *	Unit test for Message/Header/AttributedValue.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\AttributedValue;
use CeusMedia\MailTest\TestCase;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Message\Header\AttributedValue
 */
class AttributedValueTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 *	@covers		::getValue
	 */
	public function testConstructor(): void
	{
		$val	= new AttributedValue( 'test-value' );
		self::assertSame( 'test-value', $val->getValue() );
	}

	/**
	 *	@covers		::__construct
	 *	@covers		::getAttributes
	 */
	public function testConstructorWithAttributes(): void
	{
		$val	= new AttributedValue( 'filename.txt', ['charset' => 'UTF-8'] );
		self::assertSame( ['charset' => 'UTF-8'], $val->getAttributes() );
	}

	/**
	 *	@covers		::setValue
	 *	@covers		::getValue
	 */
	public function testSetValue(): void
	{
		$val	= new AttributedValue( 'old' );
		self::assertSame( $val, $val->setValue( 'new' ) );
		self::assertSame( 'new', $val->getValue() );
	}

	/**
	 *	@covers		::setAttribute
	 *	@covers		::getAttribute
	 */
	public function testSetAttribute(): void
	{
		$val	= new AttributedValue( 'file.txt' );
		self::assertSame( $val, $val->setAttribute( 'charset', 'UTF-8' ) );
		self::assertSame( 'UTF-8', $val->getAttribute( 'charset' ) );
	}

	/**
	 *	@covers		::getAttribute
	 */
	public function testGetAttributeDefault(): void
	{
		$val	= new AttributedValue( 'file.txt' );
		self::assertNull( $val->getAttribute( 'missing' ) );
		self::assertSame( 'fallback', $val->getAttribute( 'missing', 'fallback' ) );
	}

	/**
	 *	@covers		::hasAttributes
	 */
	public function testHasAttributes(): void
	{
		$val	= new AttributedValue( 'file.txt' );
		self::assertFalse( $val->hasAttributes() );

		$val->setAttribute( 'charset', 'UTF-8' );
		self::assertTrue( $val->hasAttributes() );
	}

	/**
	 *	@covers		::setAttributes
	 */
	public function testSetAttributes(): void
	{
		$val	= new AttributedValue( 'file.txt' );
		self::assertSame( $val, $val->setAttributes( ['charset' => 'ISO-8859-1', 'name' => 'test'] ) );
		self::assertSame( 'ISO-8859-1', $val->getAttribute( 'charset' ) );
		self::assertSame( 'test', $val->getAttribute( 'name' ) );
		self::assertTrue( $val->hasAttributes() );
	}

	/**
	 *	@covers		::toString
	 *	@covers		::__toString
	 */
	public function testToString(): void
	{
		$val	= new AttributedValue( 'file.txt' );
		self::assertSame( 'file.txt', (string) $val );
		self::assertSame( 'file.txt', $val->toString() );
	}

	/**
	 *	@covers		::toString
	 */
	public function testToStringWithAttributes(): void
	{
		$val	= new AttributedValue( 'file.txt', ['charset' => 'UTF-8'] );
		$str	= (string) $val;
		self::assertStringContainsString( 'file.txt', $str );
		self::assertStringContainsString( 'charset', $str );
	}
}
