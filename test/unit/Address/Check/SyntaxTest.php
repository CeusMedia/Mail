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
/*	public function testCheckValidSimple()
	{
		$checker	= new Syntax();
		$expected	= Syntax::MODE_FILTER;
		$actual		= $checker->check( "foo.bar@example.com" );
		$this->assertEquals( $expected, $actual );
	}*/

	/**
	 *	@covers		::check
	 */
/*	public function testCheckValidExtended()
	{
		$expected	= 2;
		$actual		= Syntax::check( "foo+bar!@example.com" );
		$this->assertEquals( $expected, $actual );
	}*/

	/**
	 *	@covers		::check
	 */
/*	public function testCheckInvalid()
	{
		$expected	= 0;

		$actual		= Syntax::check( "foo.bar.@example.com", FALSE );
		$this->assertEquals( $expected, $actual );

		$actual		= Syntax::check( "@example.com", FALSE );
		$this->assertEquals( $expected, $actual );

		$actual		= Syntax::check( "test", FALSE );
		$this->assertEquals( $expected, $actual );

		$actual		= Syntax::check( "_____@####.++", FALSE );
		$this->assertEquals( $expected, $actual );
	}*/

	/**
	 *	@covers		::check
	 */
/*	public function testCheckException()
	{
		$this->expectException( "InvalidArgumentException" );
		Syntax::check( "foo.bar.@example.com" );
	}*/

	/**
	 *	@covers		::check
	 */
/*	public function testIsValidValidSimple()
	{
		$expected	= 1;
		$actual		= Syntax::check( "foo.bar@example.com" );
		$this->assertEquals( $expected, $actual );
	}*/

	/**
	 *	@covers		::check
	 */
/*	public function testIsValidValidExtended()
	{
		$expected	= 2;
		$actual		= Syntax::check( "foo+bar!@example.com" );
		$this->assertEquals( $expected, $actual );
	}*/

	/**
	 *	@covers		::isValid
	 */
/*	public function testIsValidInvalid()
	{
		$expected	= FALSE;

		$actual		= Syntax::isValid( "foo.bar.@example.com" );
		$this->assertEquals( $expected, $actual );

		$actual		= Syntax::isValid( "@example.com" );
		$this->assertEquals( $expected, $actual );

		$actual		= Syntax::isValid( "test" );
		$this->assertEquals( $expected, $actual );

		$actual		= Syntax::isValid( "_____@####.++" );
		$this->assertEquals( $expected, $actual );
	}*/
}
