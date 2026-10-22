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
		self::assertEquals( new Encoding(), $instance );
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
			self::assertEquals( $expected, $actual );

			$string		= '=?UTF-8?B?W0dydXBwZW5wb3N0XSBHcnVwcGUgIkRlbGkgMTI0IjogTWlrZSBpc3QgYmVpZ2V0cmV0ZW4gdW5kIGJlbsO2dGlndCBGcmVpZ2FiZQ==?=';
			$actual		= $encoder->decodeIfNeeded( $string );
			self::assertEquals( $expected, $actual );
		}
	}

	/**
	 *	White space between adjacent encoded words is ignored (RFC 2047 6.2), also across folded lines;
	 *	all decode strategies must give the same result.
	 */
	public function testDecodeAdjacentEncodedWords(): void
	{
		$cases	= [
			"=?UTF-8?Q?Oper?= =?UTF-8?Q?ation?="											=> 'Operation',
			"Ihr Termin =?UTF-8?Q?=E2=80=93=20Oper?=\r\n =?UTF-8?Q?ation=20=28Laser=29?="		=> 'Ihr Termin – Operation (Laser)',
			"=?UTF-8?B?w6Q=?= und =?UTF-8?Q?=C3=B6?="										=> 'ä und ö',
			"Hallo\r\n Welt =?UTF-8?Q?=C3=A4?="												=> 'Hallo Welt ä',
			"=?ISO-8859-1?Q?Gr=FC=DFe?= =?UTF-8?Q?_=E2=82=AC?="								=> 'Grüße €',
			"=?UTF-8*de?Q?Stra=C3=9Fe?="														=> 'Straße',
			"=?utf-8?q?ohne_Leerraum?=dahinter"													=> 'ohne Leerraumdahinter',
		];
		$encoder	= Encoding::getInstance();
		foreach( Encoding::DECODE_STRATEGIES as $strategy ){
			//  strict iconv rejects some cases; the own strategy keeps white space between encoded words
			if( in_array( $strategy, [Encoding::DECODE_STRATEGY_ICONV_STRICT, Encoding::DECODE_STRATEGY_IMPL], TRUE ) )
				continue;
			$encoder->setDecodeStrategy( $strategy );
			foreach( $cases as $input => $expected )
				self::assertSame( $expected, $encoder->decodeIfNeeded( $input ), 'strategy '.$strategy.': '.$input );
		}
	}

	/**
	 *	In Q encoding "_" is a space, an encoded "=5F" is an underscore.
	 */
	public function testDecodeUnderscoreInQEncoding(): void
	{
		self::assertSame( 'a_b c', Encoding::getInstance()->decodeByOwnStrategy( '=?UTF-8?Q?a=5Fb_c?=' ) );
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeeded()
	{
		$encoder	= Encoding::getInstance();

		$expected	= 'no_need_to_encode';
		self::assertEquals( $expected, $encoder->encodeIfNeeded( $expected ) );

		$encoder->setEncodeStrategy( Encoding::ENCODE_STRATEGY_MB );
		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		self::assertEquals( $expected, $actual );

		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		self::assertEquals( $expected, $actual );

		$encoder->setEncodeStrategy( Encoding::ENCODE_STRATEGY_IMPL );
		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		self::assertEquals( $expected, $actual );

		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		self::assertEquals( $expected, $actual );
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
			self::assertEquals( $strategy, $encoder->decodeStrategy );
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
			self::assertEquals( $strategy, $encoder->decodeStrategyFallback );
		}
	}

	/**
	 *	If the decode strategy fails, the fallback strategy is used, without a PHP warning.
	 *	@covers		::decodeIfNeeded
	 */
	public function testDecodeIfNeededUsesFallback(): void
	{
		//  iconv rejects raw 8-bit text in a header (not RFC compliant, but common), the own strategy decodes it
		$encoder	= ( new Encoding() )
			->setDecodeStrategy( Encoding::DECODE_STRATEGY_ICONV )
			->setDecodeStrategyFallback( Encoding::DECODE_STRATEGY_IMPL );
		$warnings	= [];
		set_error_handler( static function( int $code, string $message ) use ( &$warnings ): bool {
			$warnings[]	= $message;
			return TRUE;
		} );
		try{
			self::assertSame( 'Köln Köln', $encoder->decodeIfNeeded( 'Köln =?UTF-8?Q?K=C3=B6ln?=' ) );
		}
		finally{
			restore_error_handler();
		}
		self::assertSame( [], $warnings );
	}

	/**
	 *	Without fallback, the failure of the decode strategy is reported.
	 *	@covers		::decodeIfNeeded
	 *	@covers		::setDecodeStrategyFallback
	 */
	public function testDecodeIfNeededWithoutFallback(): void
	{
		$encoder	= ( new Encoding() )
			->setDecodeStrategy( Encoding::DECODE_STRATEGY_ICONV )
			->setDecodeStrategyFallback( 0 );
		self::assertSame( 0, $encoder->decodeStrategyFallback );
		$this->expectException( 'RuntimeException' );
		$encoder->decodeIfNeeded( 'Köln =?UTF-8?Q?K=C3=B6ln?=' );
	}

	/**
	 *	@covers		::setEncodeStrategyFallback
	 */
	public function testSetEncodeStrategyFallbackNone(): void
	{
		$encoder	= ( new Encoding() )->setEncodeStrategyFallback( 0 );
		self::assertSame( 0, $encoder->encodeStrategyFallback );
		self::assertSame( Encoding::ENCODE_STRATEGY_IMPL, ( new Encoding() )->encodeStrategyFallback );
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
			self::assertEquals( $strategy, $encoder->encodeStrategy );
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
			self::assertEquals( $strategy, $encoder->encodeStrategyFallback );
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
