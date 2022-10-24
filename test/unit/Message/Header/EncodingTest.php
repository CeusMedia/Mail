<?php
/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\Encoding;
use CeusMedia\MailTest\TestCase;
use PHPUnit_Framework_TestCase as PhpUnitTestCase;

/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Encoding
 */
class EncodingTest extends PhpUnitTestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance()
	{
		$instance	= Encoding::getInstance();
		$this->assertEquals( new Encoding(), $instance );
	}

	/**
	 *	@covers		::decodeIfNeeded
	 *	@covers		::decodeByOwnStrategy
	 */
	public function testDecodeIfNeeded()
	{
		$encoder	= Encoding::getInstance();

		foreach( Encoding::DECODE_STRATEGIES as $strategy ){
			$encoder->setDecodeStrategy( $strategy );
			$expected	= '[Gruppenpost] Gruppe "Deli 124": Mike ist beigetreten und benötigt Freigabe';

			$string		= '=?UTF-8?Q?[Gruppenpost]_Gruppe_"Deli_124":_Mike_ist_beigetreten_und_ben?=
		=?UTF-8?Q?=C3=B6tigt_Freigabe?=';
			$actual		= $encoder->decodeIfNeeded( $string );
			$expected	= '[Gruppenpost] Gruppe "Deli 124": Mike ist beigetreten und benötigt Freigabe';
			$this->assertEquals( $expected, $actual );

			$string		= '=?UTF-8?B?W0dydXBwZW5wb3N0XSBHcnVwcGUgIkRlbGkgMTI0IjogTWlrZSBpc3QgYmVpZ2V0cmV0ZW4gdW5kIGJlbsO2dGlndCBGcmVpZ2FiZQ==?=';
			$actual		= $encoder->decodeIfNeeded( $string );
			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeeded()
	{
		$encoder	= Encoding::getInstance();

		$expected	= 'no_need_to_encode';
		$this->assertEquals( $expected, $encoder->encodeIfNeeded( $expected ) );

		$encoder->setEncodeStrategy( Encoding::ENCODE_STRATEGY_MB );
		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );

		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );

		$encoder->setEncodeStrategy( Encoding::ENCODE_STRATEGY_IMPL );
		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );

		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeededException()
	{
		$encoder	= Encoding::getInstance();

		$this->expectException( 'RuntimeException' );
		$encoder->encodeIfNeeded( "ÄÖÜ", "_invalid_" );
	}

	/**
	 *	@covers		::setDecodeStrategy
	 */
	public function testSetDecodeStrategy()
	{
		$encoder	= Encoding::getInstance();

		foreach( Encoding::DECODE_STRATEGIES as $strategy ){
			$encoder->setDecodeStrategy( $strategy );
			$this->assertEquals( $strategy, $encoder->decodeStrategy );
		}
	}

	/**
	 *	@covers		::setDecodeStrategy
	 */
	public function testSetDecodeStrategyException()
	{
		$encoder	= Encoding::getInstance();

		$this->expectException( 'RangeException' );
		$encoder->setDecodeStrategy( -1 );
	}

	/**
	 *	@covers		::setDecodeStrategyFallback
	 */
	public function testSetDecodeStrategyFallback()
	{
		$encoder	= Encoding::getInstance();

		foreach( Encoding::DECODE_STRATEGIES as $strategy ){
			$encoder->setDecodeStrategyFallback( $strategy );
			$this->assertEquals( $strategy, $encoder->decodeStrategyFallback );
		}
	}

	/**
	 *	@covers		::setDecodeStrategyFallback
	 */
	public function testSetDecodeStrategyFallbackException()
	{
		$encoder	= Encoding::getInstance();

		$this->expectException( 'RangeException' );
		$encoder->setDecodeStrategyFallback( -1 );
	}

	/**
	 *	@covers		::setEncodeStrategy
	 */
	public function testSetEncodeStrategy()
	{
		$encoder	= Encoding::getInstance();

		foreach( Encoding::ENCODE_STRATEGIES as $strategy ){
			$encoder->setEncodeStrategy( $strategy );
			$this->assertEquals( $strategy, $encoder->encodeStrategy );
		}
	}

	/**
	 *	@covers		::setEncodeStrategy
	 */
	public function testSetEncodeStrategyException()
	{
		$encoder	= Encoding::getInstance();

		$this->expectException( 'RangeException' );
		$encoder->setEncodeStrategy( -1 );
	}

	/**
	 *	@covers		::setEncodeStrategyFallback
	 */
	public function testSetEncodeStrategyFallback()
	{
		$encoder	= Encoding::getInstance();

		foreach( Encoding::ENCODE_STRATEGIES as $strategy ){
			$encoder->setEncodeStrategyFallback( $strategy );
			$this->assertEquals( $strategy, $encoder->encodeStrategyFallback );
		}
	}

	/**
	 *	@covers		::setEncodeStrategyFallback
	 */
	public function testSetEncodeStrategyFallbackException()
	{
		$encoder	= Encoding::getInstance();

		$this->expectException( 'RangeException' );
		$encoder->setEncodeStrategyFallback( -1 );
	}
}
