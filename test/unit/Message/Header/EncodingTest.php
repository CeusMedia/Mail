<?php
/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\Encoding;
use CeusMedia\Mail\Test\TestCase;
use PHPUnit_Framework_TestCase as PhpUnitTestCase;

/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Encoding
 */
class EncodingTest extends PhpUnitTestCase
{
	/**
	 *	@covers		::decodeIfNeeded
	 */
	public function testDecodeIfNeeded()
	{
		$expected	= '[Gruppenpost] Gruppe "Deli 124": Mike ist beigetreten und benötigt Freigabe';

		$string		= '=?UTF-8?Q?[Gruppenpost]_Gruppe_"Deli_124":_Mike_ist_beigetreten_und_ben?=
	=?UTF-8?Q?=C3=B6tigt_Freigabe?=';
		$actual		= Encoding::decodeIfNeeded( $string );
		$expected	= '[Gruppenpost] Gruppe "Deli 124": Mike ist beigetreten und benötigt Freigabe';
		$this->assertEquals( $expected, $actual );

		$string		= '=?UTF-8?B?W0dydXBwZW5wb3N0XSBHcnVwcGUgIkRlbGkgMTI0IjogTWlrZSBpc3QgYmVpZ2V0cmV0ZW4gdW5kIGJlbsO2dGlndCBGcmVpZ2FiZQ==?=';
		$actual		= Encoding::decodeIfNeeded( $string );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeeded()
	{
		$expected	= 'no_need_to_encode';
		$this->assertEquals( $expected, Encoding::encodeIfNeeded( $expected ) );


		Encoding::setEncodeStrategy( Encoding::ENCODE_STRATEGY_MB );
		$actual	= Encoding::encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );

		$actual	= Encoding::encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );

		Encoding::setEncodeStrategy( Encoding::ENCODE_STRATEGY_IMPL );
		$actual	= Encoding::encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );

		$actual	= Encoding::encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeededException()
	{
		$this->expectException( 'RangeException' );
		Encoding::encodeIfNeeded( "ÄÖÜ", "_invalid_" );
	}

	/**
	 *	@covers		::setDecodeStrategy
	 */
	public function testSetDecodeStrategy()
	{
		foreach( Encoding::DECODE_STRATEGIES as $strategy ){
			Encoding::setDecodeStrategy( $strategy );
			$this->assertEquals( $strategy, Encoding::$decodeStrategy );
		}
	}

	/**
	 *	@covers		::setDecodeStrategy
	 */
	public function testSetDecodeStrategyException()
	{
		$this->expectException( 'RangeException' );
		Encoding::setDecodeStrategy( -1 );
	}

	/**
	 *	@covers		::setEncodeStrategy
	 */
	public function testSetEncodeStrategy()
	{
		foreach( Encoding::ENCODE_STRATEGIES as $strategy ){
			Encoding::setEncodeStrategy( $strategy );
			$this->assertEquals( $strategy, Encoding::$encodeStrategy );
		}
	}

	/**
	 *	@covers		::setEncodeStrategy
	 */
	public function testSetEncodeStrategyException()
	{
		$this->expectException( 'RangeException' );
		Encoding::setEncodeStrategy( -1 );
	}
}