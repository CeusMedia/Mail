<?php
/**
 *	Unit test for string helper.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Util
 */

namespace CeusMedia\MailTest\Unit\Util;

use CeusMedia\Mail\Util\Str;
use CeusMedia\MailTest\TestCase;
use RuntimeException;

/**
 *	Unit test for string helper.
 *	PCRE failures are reported by exception with reason, not returned like no match.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Util
 *  @coversDefaultClass \CeusMedia\Mail\Util\Str
 */
class StrTest extends TestCase
{
	/**	@var	string		Invalid UTF-8, lets PCRE fail on patterns with modifier u */
	private const INVALID_UTF8	= "a\xC3(";

	/**
	 *	@covers		::hasContent
	 */
	public function testHasContent(): void
	{
		self::assertTrue( Str::hasContent( 'a' ) );
		self::assertTrue( Str::hasContent( ' a ' ) );
		self::assertTrue( Str::hasContent( '0' ) );
		self::assertFalse( Str::hasContent( '' ) );
		self::assertFalse( Str::hasContent( " \t\r\n" ) );
		self::assertFalse( Str::hasContent( NULL ) );
	}

	/**
	 *	@covers		::regMatch
	 */
	public function testRegMatch(): void
	{
		self::assertTrue( Str::regMatch( '/^(\d+)-(\d+)$/', '12-34', $matches ) );
		self::assertSame( ['12-34', '12', '34'], $matches );
		self::assertFalse( Str::regMatch( '/^\d+$/', 'abc', $matches ) );
		self::assertSame( [], $matches );
	}

	/**
	 *	@covers		::regMatch
	 */
	public function testRegMatchFailure(): void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Regular matching failed: Malformed UTF-8 characters' );
		Str::regMatch( '/a/u', self::INVALID_UTF8 );
	}

	/**
	 *	@covers		::regMatchAll
	 */
	public function testRegMatchAll(): void
	{
		self::assertSame( [['a=1', 'a', '1'], ['b=2', 'b', '2']], Str::regMatchAll( '/(\w)=(\d)/', 'a=1; b=2' ) );
		self::assertSame( [], Str::regMatchAll( '/(\w)=(\d)/', 'none' ) );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Parameters: Malformed UTF-8 characters' );
		Str::regMatchAll( '/a/u', self::INVALID_UTF8, 'Parameters' );
	}

	/**
	 *	@covers		::regReplace
	 *	@covers		::regSplit
	 */
	public function testRegReplaceAndSplitFailure(): void
	{
		self::assertSame( 'a-b', Str::regReplace( '/ +/', '-', 'a   b' ) );
		self::assertSame( ['a', 'b'], Str::regSplit( '/ +/', 'a   b' ) );
		foreach( [
			'replace'	=> static fn() => Str::regReplace( '/a/u', '', self::INVALID_UTF8 ),
			'split'		=> static fn() => Str::regSplit( '/a/u', self::INVALID_UTF8 ),
		] as $label => $call ){
			try{
				$call();
				self::fail( $label.': exception expected' );
			}
			catch( RuntimeException $e ){
				self::assertStringContainsString( ' failed: Malformed UTF-8 characters', $e->getMessage(), $label );
			}
		}
	}
}
