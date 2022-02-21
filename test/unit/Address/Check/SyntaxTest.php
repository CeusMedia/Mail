<?php
/**
 *	Unit test for mail address syntax validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Address\Check;

use CeusMedia\Mail\Address\Check\Syntax;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address syntax validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Check\Syntax
 */
class SyntaxTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$checker	= new Syntax();
		$this->assertTrue( $checker->isMode( Syntax::MODE_AUTO ) );

		$checker	= new Syntax( Syntax::MODE_FILTER );
		$this->assertTrue( $checker->isMode( Syntax::MODE_FILTER ) );

		$checker->setMode( Syntax::MODE_SIMPLE_REGEX );
		$this->assertTrue( $checker->isMode( Syntax::MODE_SIMPLE_REGEX ) );

		$checker->setMode( Syntax::MODE_EXTENDED_REGEX );
		$this->assertTrue( $checker->isMode( Syntax::MODE_EXTENDED_REGEX ) );

		$checker->setMode( Syntax::MODE_SIMPLE_REGEX | Syntax::MODE_EXTENDED_REGEX );
		$this->assertTrue( $checker->isMode( Syntax::MODE_SIMPLE_REGEX ) );
		$this->assertTrue( $checker->isMode( Syntax::MODE_EXTENDED_REGEX ) );
	}

	public function testEvaluate()
	{
		$checker	= new Syntax();
		$expected	= Syntax::MODE_FILTER | Syntax::MODE_SIMPLE_REGEX | Syntax::MODE_EXTENDED_REGEX;
		$actual		= $checker->evaluate( "foo.bar@example.com" );
		$this->assertEquals( $expected, $actual );

		$expected	= Syntax::MODE_FILTER | Syntax::MODE_EXTENDED_REGEX;
		$actual		= $checker->evaluate( "foo+bar!@example.com" );
		$this->assertEquals( $expected, $actual );

		$expected	= 0;
		$actual		= $checker->evaluate( "foo.bar.@example.com" );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckValidAll()
	{
		$mode		= Syntax::MODE_ALL;
		$checker	= new Syntax( $mode );
		$expected	= Syntax::MODE_ALL | Syntax::MODE_FILTER | Syntax::MODE_SIMPLE_REGEX | Syntax::MODE_EXTENDED_REGEX;
		$actual		= $checker->check( "foo+bar!@example.com" );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckValidFilter()
	{
		$mode		= Syntax::MODE_FILTER;
		$checker	= new Syntax( $mode );
		$actual		= $checker->check( "foo.bar@example.com" );
		$this->assertEquals( $mode, $actual );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckValidSimple()
	{
		$mode		= Syntax::MODE_SIMPLE_REGEX;
		$checker	= new Syntax( $mode );
		$actual		= $checker->check( "foo.bar@example.com" );
		$this->assertEquals( $mode, $actual );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckValidExtended()
	{
		$mode		= Syntax::MODE_EXTENDED_REGEX;
		$checker	= new Syntax( $mode );
		$actual		= $checker->check( "foo+bar!@example.com" );
		$this->assertEquals( $mode, $actual );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckInvalid()
	{
		$checker	= new Syntax( Syntax::MODE_ALL );
		$this->assertEquals( 0, $checker->check( "foo.bar.@example.com", FALSE ) );
		$this->assertEquals( 0, $checker->check( "@example.com", FALSE ) );
		$this->assertEquals( 0, $checker->check( "test", FALSE ) );
		$this->assertEquals( 0, $checker->check( "_____@####.++", FALSE ) );
	}

	/**
	 *	@covers		::check
	 */
	public function testCheckException()
	{
		$checker	= new Syntax();
		$this->expectException( "InvalidArgumentException" );
		$checker->check( "_____@####.++" );
	}

	/**
	 *	@covers		::isValidByMode
	 */
	public function testIsValidByModeExceptionOnInvalidMode()
	{
		$checker	= new Syntax();
		$this->expectException( "InvalidArgumentException" );
		$checker->isValidByMode( "not_important_here", -1 );
	}

	/**
	 *	@covers		::isValid
	 */
	public function testIsValidValidSimple()
	{
		$checker	= new Syntax( Syntax::MODE_SIMPLE_REGEX );
		$this->assertTrue( $checker->isValid( "foo.bar@example.com" ) );
	}

	/**
	 *	@covers		::isValid
	 */
	public function testIsValidValidExtended()
	{
		$checker	= new Syntax( Syntax::MODE_EXTENDED_REGEX );
		$this->assertTrue( $checker->isValid( "foo+bar!@example.com" ) );
	}

	/**
	 *	@covers		::isValid
	 */
	public function testIsValidInvalid()
	{
		$checker	= new Syntax();
		$this->assertFalse( $checker->isValid( "foo.bar.@example.com" ) );
		$this->assertFalse( $checker->isValid( "@example.com" ) );
		$this->assertFalse( $checker->isValid( "test" ) );
		$this->assertFalse( $checker->isValid( "_____@####.++" ) );
	}

	/**
	 *	@covers		::isMode
	 *	@covers		::setMode
	 */
	public function testSetMode()
	{
		$checker	= new Syntax();
		$checker->setMode( Syntax::MODE_FILTER );
		$this->assertTrue( $checker->isMode( Syntax::MODE_FILTER ) );

		$checker->setMode( Syntax::MODE_SIMPLE_REGEX );
		$this->assertTrue( $checker->isMode( Syntax::MODE_SIMPLE_REGEX ) );

		$checker->setMode( Syntax::MODE_EXTENDED_REGEX );
		$this->assertTrue( $checker->isMode( Syntax::MODE_EXTENDED_REGEX ) );

		$checker->setMode( Syntax::MODE_SIMPLE_REGEX | Syntax::MODE_EXTENDED_REGEX );
		$this->assertTrue( $checker->isMode( Syntax::MODE_SIMPLE_REGEX ) );
		$this->assertTrue( $checker->isMode( Syntax::MODE_EXTENDED_REGEX ) );
	}
}
