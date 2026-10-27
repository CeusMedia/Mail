<?php
/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Address\Collection\Parser as AddressCollectionParser;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding;
use CeusMedia\MailTest\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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
	 */
	public function testDecodeIfNeeded()
	{
		$encoder	= Encoding::getInstance();
		$expected	= '[Gruppenpost] Gruppe "Deli 124": Mike ist beigetreten und benötigt Freigabe';

		$string		= '=?UTF-8?Q?[Gruppenpost]_Gruppe_"Deli_124":_Mike_ist_beigetreten_und_ben?=
	=?UTF-8?Q?=C3=B6tigt_Freigabe?=';
		self::assertEquals( $expected, $encoder->decodeIfNeeded( $string ) );

		$string		= '=?UTF-8?B?W0dydXBwZW5wb3N0XSBHcnVwcGUgIkRlbGkgMTI0IjogTWlrZSBpc3QgYmVpZ2V0cmV0ZW4gdW5kIGJlbsO2dGlndCBGcmVpZ2FiZQ==?=';
		self::assertEquals( $expected, $encoder->decodeIfNeeded( $string ) );
	}

	/**
	 *	White space between adjacent encoded words is ignored (RFC 2047 6.2), also across folded lines.
	 *	@covers		::decodeIfNeeded
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
		foreach( $cases as $input => $expected )
			self::assertSame( $expected, $encoder->decodeIfNeeded( $input ), $input );
	}

	/**
	 *	Decoding is stable for cases, which iconv_mime_decode and mb_decode_mimeheader fail on, without PHP warnings:
	 *	character split across words, unknown charset, malformed words, raw 8bit text.
	 *	@covers		::decodeIfNeeded
	 */
	public function testDecodeIfNeededStable(): void
	{
		$cases	= [
			"=?UTF-8?B?SsM=?= =?UTF-8?B?vHJnZW4=?="			=> 'Jürgen',					//  "ü" split across words
			"=?UTF-8?Q?J=C3?=\r\n =?UTF-8?Q?=BCrgen?="			=> 'Jürgen',					//  "ü" split, folded
			"=?X-UNKNOWN?Q?abc?="								=> 'abc',						//  unknown charset
			"Köln =?UTF-8?Q?K=C3=B6ln?="						=> 'Köln Köln',					//  raw 8bit text (not RFC compliant, but common)
			"=?UTF-8?X?abc?= =?UTF-8?Q?broken"					=> '=?UTF-8?X?abc?= =?UTF-8?Q?broken',	//  malformed: kept
			"a =?UTF-8?Q?=C3=BC?= b"							=> 'a ü b',						//  space to text kept
			"=?UTF-8?Q?A?= =?*x?Q?B?= =?UTF-8?Q?C?="			=> 'A=?*x?Q?B?=C',				//  unreadable word kept in order
			"a =?UTF-8?B?".base64_encode( "\r\nBcc: e@example.org" )."?="	=> 'a  Bcc: e@example.org',	//  decoded line breaks: space (no injection)
		];
		$encoder	= Encoding::getInstance();
		$warnings	= [];
		set_error_handler( static function( int $code, string $message ) use ( &$warnings ): bool {
			$warnings[]	= $message;
			return TRUE;
		} );
		try{
			foreach( $cases as $input => $expected )
				self::assertSame( $expected, $encoder->decodeIfNeeded( $input ), $input );
		}
		finally{
			restore_error_handler();
		}
		self::assertSame( [], $warnings );
	}

	/**
	 *	In Q encoding "_" is a space, an encoded "=5F" is an underscore.
	 */
	public function testDecodeUnderscoreInQEncoding(): void
	{
		self::assertSame( 'a_b c', Encoding::getInstance()->decodeIfNeeded( '=?UTF-8?Q?a=5Fb_c?=' ) );
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeeded()
	{
		$encoder	= Encoding::getInstance();

		$expected	= 'no_need_to_encode';
		self::assertEquals( $expected, $encoder->encodeIfNeeded( $expected ) );

		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ" );
		$expected	= "=?UTF-8?B?".base64_encode( "ÄÖÜ" )."?=";
		self::assertEquals( $expected, $actual );

		$actual	= $encoder->encodeIfNeeded( "ÄÖÜ", "quoted-printable" );
		$expected	= "=?UTF-8?Q?".quoted_printable_encode( "ÄÖÜ" )."?=";
		self::assertEquals( $expected, $actual );
	}

	/**
	 *	Line breaks are never returned unencoded, they would start a new header (header injection).
	 *	Plain values may contain word characters, space, tab, dot, slash, colon, hash and hyphen.
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeededLineBreaks()
	{
		$encoder	= Encoding::getInstance();
		foreach( ["Hi\r\nX: 1", "Hi\nX: 1", "Hi\rX: 1"] as $value ){
			$actual	= $encoder->encodeIfNeeded( $value );
			self::assertStringStartsWith( '=?UTF-8?B?', $actual );
			self::assertSame( $value, iconv_mime_decode( $actual, 0, 'UTF-8' ) );
		}
		self::assertSame( "Re: Ticket #12 - 1.2/3\tok", $encoder->encodeIfNeeded( "Re: Ticket #12 - 1.2/3\tok" ) );
	}

	/**
	 *	@return		array<string,array{string}>
	 */
	public static function encodings(): array
	{
		return [
			'base64'			=> ['base64'],
			'quoted-printable'	=> ['quoted-printable'],
		];
	}

	/**
	 *	Encoded words: whole characters each, at most 75 characters, lines at most 76 characters incl. header name.
	 *	@covers		::encodeIfNeeded
	 *	@covers		::encodeWords
	 */
	#[DataProvider( 'encodings' )]
	public function testEncodeIfNeededFoldsWithOffset( string $encoding ): void
	{
		$encoder	= new Encoding();
		$subject	= str_repeat( 'Grüße aus Köln – ', 12 ).'Ende';
		$encoded	= $encoder->encodeIfNeeded( $subject, $encoding, TRUE, strlen( 'Subject: ' ) );
		$lines		= explode( Message::$delimiter, 'Subject: '.$encoded );
		self::assertGreaterThan( 3, count( $lines ) );
		foreach( $lines as $nr => $line ){
			self::assertLessThanOrEqual( 76, strlen( $line ), 'line '.$nr );
			self::assertSame( 1, preg_match( '/^(?:Subject:)? (=\?UTF-8\?[BQ]\?[^?]+\?=)$/', $line, $matches ), 'line '.$nr );
			self::assertLessThanOrEqual( 75, strlen( $matches[1] ) );
			self::assertTrue( mb_check_encoding( (string) iconv_mime_decode( $matches[1], 0, 'UTF-8' ), 'UTF-8' ), 'whole characters in line '.$nr );
		}
		self::assertSame( $subject, $encoder->decodeIfNeeded( $encoded ) );
		self::assertSame( 'Subject: '.$subject, iconv_mime_decode( 'Subject: '.$encoded, 0, 'UTF-8' ) );

		$unfolded	= $encoder->encodeIfNeeded( $subject, $encoding, FALSE );
		self::assertStringNotContainsString( "\n", $unfolded );
		self::assertSame( $subject, $encoder->decodeIfNeeded( $unfolded ) );
	}

	/**
	 *	Q encoding by RFC 2047, not by body quoted-printable: underscore, quotes, comma, question mark encoded,
	 *	space as underscore. Result is valid in phrases, too (RFC 2047, 5 (3)).
	 *	@covers		::encodeWords
	 */
	public function testEncodeWordsQuotedPrintable(): void
	{
		$encoder	= new Encoding();
		$actual		= $encoder->encodeWords( 'a_b, ö "x" ?= 1+1', 'q' );
		self::assertSame( '=?UTF-8?Q?a=5Fb=2C_=C3=B6_=22x=22_=3F=3D_1+1?=', $actual );
		self::assertSame( 'a_b, ö "x" ?= 1+1', $encoder->decodeIfNeeded( $actual ) );
		self::assertSame( 1, preg_match( '/^=\?UTF-8\?Q\?[A-Za-z0-9!*+\/=_-]+\?=$/', $actual ) );
	}

	/**
	 *	Printable ASCII stays plain, folded at spaces if too long.
	 *	@covers		::encodeIfNeeded
	 *	@covers		::foldPlain
	 */
	public function testEncodeIfNeededPlainAscii(): void
	{
		$encoder	= new Encoding();
		self::assertSame( 'Re: Hello (world)! 100% @home', $encoder->encodeIfNeeded( 'Re: Hello (world)! 100% @home' ) );

		$subject	= trim( str_repeat( 'word ', 30 ) );
		$folded		= $encoder->encodeIfNeeded( $subject, 'base64', TRUE, 9 );
		$lines		= explode( Message::$delimiter, 'Subject: '.$folded );
		self::assertCount( 3, $lines );
		foreach( $lines as $nr => $line ){
			self::assertLessThanOrEqual( 76, strlen( $line ) );
			self::assertGreaterThan( 66, strlen( $line ) + ( 2 === $nr ? 60 : 0 ) );			//  filled, not folded too early
			if( 0 !== $nr )
				self::assertStringStartsWith( ' word', $line );
		}
		self::assertSame( $subject, str_replace( Message::$delimiter, '', $folded ) );
		self::assertSame( $subject, $encoder->encodeIfNeeded( $subject, 'base64', FALSE ) );

		//  looks like an encoded word, so it is encoded
		self::assertStringStartsWith( '=?UTF-8?B?', $encoder->encodeIfNeeded( 'Price =?UTF-8?B?eA==?=' ) );
	}

	/**
	 *	Address lists are decoded name by name, names with special characters are quoted.
	 *	@covers		::decodeAddressListIfNeeded
	 */
	public function testDecodeAddressListIfNeeded(): void
	{
		$encoder	= new Encoding();
		$value		= '=?UTF-8?B?'.base64_encode( 'Doe, Jürgen' ).'?= <a@example.org>, '
			.'=?UTF-8?Q?J=C3=BCrgen?='.Message::$delimiter.' =?UTF-8?Q?_M=C3=BCller?= <b@example.org>, '
			.'"=?UTF-8?B?'.base64_encode( 'Zoë "Z"' ).'?=" <c@example.org>, '
			.'Dr. =?UTF-8?Q?Z=C3=B6e?= <d@example.org>, e@example.org';
		$expected	= '"Doe, Jürgen" <a@example.org>, Jürgen Müller <b@example.org>, '
			.'"Zoë \\"Z\\"" <c@example.org>, "Dr. Zöe" <d@example.org>, e@example.org';
		self::assertSame( $expected, $encoder->decodeAddressListIfNeeded( $value ) );
		self::assertSame( 'a@example.org, "Doe, John" <b@example.org>', $encoder->decodeAddressListIfNeeded( 'a@example.org, "Doe, John" <b@example.org>' ) );

		//  encoded word mixed with plain word: whole name quoted, parsed as one address
		$value	= $encoder->decodeAddressListIfNeeded( '=?UTF-8?B?'.base64_encode( 'Doe, J' ).'?= Smith <a@example.org>' );
		self::assertSame( '"Doe, J Smith" <a@example.org>', $value );
		$addresses	= AddressCollectionParser::getInstance()->setMethod( AddressCollectionParser::METHOD_OWN )->parse( $value )->getAll();
		self::assertCount( 1, $addresses );
		self::assertSame( 'Doe, J Smith', $addresses[0]->getName( FALSE ) );
	}

	/**
	 *	@covers		::encodeIfNeeded
	 */
	public function testEncodeIfNeededException()
	{
		$encoder	= Encoding::getInstance();

		$this->expectException( 'RangeException' );
		$encoder->encodeIfNeeded( "ÄÖÜ", "_invalid_" );
	}

	/**
	 *	@covers		::isPrintableAscii
	 */
	public function testIsPrintableAscii(): void
	{
		self::assertTrue( Encoding::isPrintableAscii( '' ) );
		self::assertTrue( Encoding::isPrintableAscii( "Re: Ticket #12 - 1.2/3\tok ~" ) );
		self::assertFalse( Encoding::isPrintableAscii( 'Grüße' ) );
		self::assertFalse( Encoding::isPrintableAscii( "a\r\nb" ) );
		self::assertFalse( Encoding::isPrintableAscii( "a\x00b" ) );
		self::assertFalse( Encoding::isPrintableAscii( "a\x7Fb" ) );
	}

	/**
	 *	@covers		::quote
	 *	@covers		::unquote
	 *	@covers		::unfold
	 */
	public function testQuoteUnquoteUnfold(): void
	{
		self::assertSame( '"Max \\"Mo\\" \\\\ Co"', Encoding::quote( 'Max "Mo" \\ Co' ) );
		self::assertSame( 'Max "Mo" \\ Co', Encoding::unquote( Encoding::quote( 'Max "Mo" \\ Co' ) ) );
		self::assertSame( 'not quoted', Encoding::unquote( 'not quoted' ) );
		self::assertSame( '"', Encoding::unquote( '"' ) );

		self::assertSame( "a, b,\tc", Encoding::unfold( "a,\r\n b,\n\tc" ) );
		self::assertSame( "a\r\nb", Encoding::unfold( "a\r\nb" ) );
	}

	/**
	 *	Many encoded words not followed by an angle address: no regex failure, value kept (was emptied).
	 *	@covers		::decodeAddressListIfNeeded
	 */
	public function testDecodeAddressListManyEncodedWords(): void
	{
		$encoder	= new Encoding();
		$value		= trim( str_repeat( '=?UTF-8?Q?a?= ', 40 ) ).' x';
		self::assertSame( $value, $encoder->decodeAddressListIfNeeded( $value ) );
		$value		= trim( str_repeat( '=?UTF-8?Q?a?= ', 3000 ) ).' <x@example.org>';
		self::assertSame( str_repeat( 'a', 3000 ).' <x@example.org>', $encoder->decodeAddressListIfNeeded( $value ) );
	}

	/**
	 *	Adjacent encoded words are decoded together: a character split across words stays intact;
	 *	encoded words in comments are decoded, too (RFC 2047, 5 (2)).
	 *	@covers		::decodeAddressListIfNeeded
	 *	@covers		::decodeWords
	 */
	public function testDecodeAddressListSplitCharacterAndComment(): void
	{
		$encoder	= new Encoding();
		self::assertSame( 'Jürgen <a@example.org>', $encoder->decodeAddressListIfNeeded( '=?UTF-8?B?SsO=?= =?UTF-8?B?vHJnZW4=?= <a@example.org>' ) );
		self::assertSame( 'Jürgen Müller <a@example.org>', $encoder->decodeAddressListIfNeeded( '=?ISO-8859-1?Q?J=FCrgen?= =?UTF-8?Q?_M=C3=BCller?= <a@example.org>' ) );
		self::assertSame( 'a@example.org (Jürgen \\(x\\))', $encoder->decodeAddressListIfNeeded( 'a@example.org (=?UTF-8?Q?J=C3=BCrgen_=28x=29?=)' ) );
	}

	/**
	 *	Large offset: first encoded word starts on next line, lines stay within 76 characters.
	 *	@covers		::encodeWords
	 */
	public function testEncodeWordsLargeOffset(): void
	{
		$encoded	= ( new Encoding() )->encodeWords( str_repeat( 'ü', 30 ), 'base64', TRUE, 70 );
		self::assertStringStartsWith( Message::$delimiter.' =?UTF-8?B?', $encoded );
		foreach( explode( Message::$delimiter, str_repeat( 'x', 70 ).$encoded ) as $line )
			self::assertLessThanOrEqual( 76, strlen( $line ) );
		self::assertSame( 'X: '.str_repeat( 'ü', 30 ), iconv_mime_decode( 'X: '.$encoded, 0, 'UTF-8' ) );
	}

	/**
	 *	Unknown charset converted to target charset of encoder.
	 *	@covers		::convertToCharset
	 */
	public function testConvertToCharset(): void
	{
		$encoder	= new Encoding();
		self::assertSame( 'Grüße', $encoder->convertToCharset( "Gr\xFC\xDFe", 'ISO-8859-1' ) );
		self::assertSame( 'Grüße', $encoder->convertToCharset( 'Grüße', 'x-unknown' ) );
		self::assertSame( 'Grüße', $encoder->convertToCharset( "Gr\xFC\xDFe", 'x-unknown' ) );

		$encoder->charset	= 'ISO-8859-15';
		self::assertSame( "Gr\xFC\xDFe", $encoder->convertToCharset( 'Grüße', 'x-unknown' ) );
		self::assertSame( "Gr\xFC\xDFe", $encoder->convertToCharset( "Gr\xFC\xDFe", 'x-unknown' ) );
	}

	/**
	 *	Long values (e.g. 100 KB quoted name or comment) do not exhaust PCRE stack: decoded, not kept encoded.
	 *	@covers		::decodeAddressListIfNeeded
	 */
	public function testDecodeAddressListLongValues(): void
	{
		$encoder	= Encoding::getInstance();
		$long		= str_repeat( 'a', 100000 );
		$cases		= [
			'"'.$long.'" =?UTF-8?Q?=C3=BC?= <a@example.org>'					=> $long.' ü <a@example.org>',
			str_repeat( 'a ', 50000 ).'=?UTF-8?Q?=C3=BC?= <a@example.org>'	=> str_repeat( 'a ', 50000 ).'ü <a@example.org>',
			'a@example.org ('.str_repeat( '\\(', 50000 ).' =?UTF-8?Q?=C3=BC?=)'	=> 'a@example.org ('.str_repeat( '\\(', 50000 ).' ü)',
		];
		foreach( $cases as $input => $expected )
			self::assertTrue( $expected === $encoder->decodeAddressListIfNeeded( $input ), substr( $input, -40 ) );
	}
}
