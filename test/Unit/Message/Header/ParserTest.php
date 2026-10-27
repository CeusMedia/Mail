<?php
/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Address\Collection\Parser as AddressCollectionParser;
use CeusMedia\Mail\Message\Header\AttributedField;
use CeusMedia\Mail\Message\Header\Encoding;
use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Parser;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance()
	{
		$instance	= Parser::getInstance();
		self::assertEquals( new Parser(), $instance );
	}

	/**
	 *	@covers		::parse
	 */
/*	public function testParse()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Header\\Parser' );

		$parser	= Parser::getInstance();
	}*/

	/**
	 *	Folded subject with encoded words spanning lines (as written by Message\Renderer).
	 */
	public function testParseFoldedEncodedSubject(): void
	{
		$header	= "Subject: Ihr Termin am Freitag, 23.10.2026 um 07:30 Uhr =?UTF-8?Q?=E2=80=93=20Oper?=\r\n =?UTF-8?Q?ation=20=28Laser=20=E2=80=93=20PRK=29?=\r\nMIME-Version: 1.0";
		$expected	= 'Ihr Termin am Freitag, 23.10.2026 um 07:30 Uhr – Operation (Laser – PRK)';
		$section	= Parser::getInstance()->parse( $header );
		self::assertSame( $expected, $section->getField( 'Subject' )?->getValue() );
		self::assertSame( '1.0', $section->getField( 'MIME-Version' )?->getValue() );
	}

	/**
	 *	Long subjects, encoded and folded by Encoding::encodeIfNeeded(), are parsed back unchanged.
	 *	The former line by line decoding inserted spaces, e.g. "Übersicht ü ber" or "Pr axis".
	 */
	public function testParseEncodedSubjectRoundTrip(): void
	{
		$subjects	= [
			'Ihr Termin bei Lasermed: Voruntersuchung am Donnerstag, 29. Oktober 2026 um 09:00 Uhr in Leipzig – Änderung',
			'Grüße aus Köln und München: Übersicht über Ihre nächsten Termine in der Lasermed-Praxis',
		];
		foreach( $subjects as $subject ){
			$header		= 'Subject: '.Encoding::getInstance()->encodeIfNeeded( $subject );
			$section	= Parser::getInstance()->parse( $header );
			self::assertSame( $subject, $section->getField( 'Subject' )?->getValue() );
		}
	}

	/**
	 *	Address lists are decoded name by name: an encoded name with comma stays one address (quoted).
	 *	Names with semicolon are not split as attributes.
	 */
	public function testParseAddressListWithEncodedNames(): void
	{
		$header	= 'To: =?UTF-8?B?'.base64_encode( 'Doe, Jürgen' ).'?= <a@example.org>,'."\r\n"
			.' =?UTF-8?B?'.base64_encode( 'Müller; Max' ).'?= <b@example.org>'."\r\n"
			.'Subject: =?UTF-8?B?'.base64_encode( 'Doe, Jürgen' ).'?='."\r\n"
			.'Reply-To: =?UTF-8?Q?Z=C3=B6e?= <c@example.org>';
		$expected	= '"Doe, Jürgen" <a@example.org>, "Müller; Max" <b@example.org>';
		$section	= Parser::getInstance()->parse( $header );
		self::assertSame( $expected, $section->getField( 'To' )?->getValue() );
		self::assertSame( [], $section->getField( 'To' )?->getAttributes() );
		self::assertSame( 'Doe, Jürgen', $section->getField( 'Subject' )?->getValue() );
		self::assertSame( 'Zöe <c@example.org>', $section->getField( 'Reply-To' )?->getValue() );

		$addresses	= AddressCollectionParser::getInstance()->parse( (string) $section->getField( 'To' )?->getValue() );
		self::assertCount( 2, $addresses );
		self::assertSame( 'Doe, Jürgen', $addresses->getAll()[0]->getName( FALSE ) );
		self::assertSame( 'Müller; Max', $addresses->getAll()[1]->getName( FALSE ) );
	}

	/**
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedHeaderValue()
	{
		$string		= 'value; key1="value1"; key2="value2"';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'value', $object->getValue() );
		$expected	= ['key1' => 'value1', 'key2' => 'value2'];
		self::assertEquals( $expected, $object->getAttributes() );

		$string		= 'form-data; name="field2"; filename="example.txt"';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'form-data', $object->getValue() );
		$expected	= ['name' => 'field2', 'filename' => 'example.txt'];
		self::assertEquals( $expected, $object->getAttributes() );

		$string		= 'A custom value; first-attribute="Umlauts-Test #1 äöüÄÖÜß"; second-attribute="Quotes-Test \"a_b\""';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'A custom value', $object->getValue() );
		$expected	= ['first-attribute' => 'Umlauts-Test #1 äöüÄÖÜß', 'second-attribute' => 'Quotes-Test "a_b"'];
		self::assertEquals( $expected, $object->getAttributes() );

		$string		= 'value; complex-key.1="Project Name \"Vanitas 2.0\""';
		$object		= Parser::parseAttributedHeaderValue( $string );
		self::assertEquals( 'value', $object->getValue() );
		$expected	= ['complex-key.1' => 'Project Name "Vanitas 2.0"'];
		self::assertEquals( $expected, $object->getAttributes() );
	}

	/**
	 *	RFC 2231: encoded values, continuations (encoded or not), charsets, precedence over plain value.
	 *	Semicolons in quoted values do not split.
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedHeaderValueRfc2231(): void
	{
		$object	= Parser::parseAttributedHeaderValue( "attachment; filename*=UTF-8''a+b%20%C3%BC.txt; size=12" );
		self::assertSame( 'attachment', $object->getValue() );
		self::assertSame( ['filename' => 'a+b ü.txt', 'size' => '12'], $object->getAttributes() );

		$object	= Parser::parseAttributedHeaderValue( "attachment;\r\n filename*0*=UTF-8'de'Gr%C3;\r\n filename*1*=%BC%C3%9Fe;\r\n filename*2=\" 1.txt\"" );
		self::assertSame( ['filename' => 'Grüße 1.txt'], $object->getAttributes() );

		$object	= Parser::parseAttributedHeaderValue( "attachment; filename*=ISO-8859-1''Gr%FC%DFe.txt" );
		self::assertSame( ['filename' => 'Grüße.txt'], $object->getAttributes() );

		$object	= Parser::parseAttributedHeaderValue( "attachment; filename=\"Grusse.txt\"; filename*=UTF-8''Gr%C3%BC%C3%9Fe.txt" );
		self::assertSame( ['filename' => 'Grüße.txt'], $object->getAttributes() );

		$object	= Parser::parseAttributedHeaderValue( 'attachment; filename="a \\"b\\"; c.txt"; size=1' );
		self::assertSame( ['filename' => 'a "b"; c.txt', 'size' => '1'], $object->getAttributes() );

		//  plain value looking like RFC 2231 is not decoded
		$object	= Parser::parseAttributedHeaderValue( "attachment; filename=\"utf-8'en'a%20b.txt\"" );
		self::assertSame( ['filename' => "utf-8'en'a%20b.txt"], $object->getAttributes() );
	}

	/**
	 *	Only Content-Type and Content-Disposition are split into value and parameters.
	 *	Unstructured values like Subject keep semicolons.
	 */
	public function testParseParametersOnlyForParameterFields(): void
	{
		$header	= "Subject: Meeting; agenda attached\r\n"
			."X-Custom: Re: offer; size=10\r\n"
			."Content-Type: text/plain;\r\n charset=\"utf-8\"; format=fixed\r\n"
			."Content-Disposition: attachment; filename=\"=?UTF-8?Q?Gr=C3=BC=C3=9Fe?=.txt\"";
		$section	= Parser::getInstance()->parse( $header );
		self::assertSame( 'Meeting; agenda attached', $section->getField( 'Subject' )?->getValue() );
		self::assertSame( 'Re: offer; size=10', $section->getField( 'X-Custom' )?->getValue() );
		self::assertSame( [], $section->getField( 'X-Custom' )?->getAttributes() );
		self::assertSame( 'text/plain', $section->getField( 'Content-Type' )?->getValue() );
		self::assertSame( ['charset' => 'utf-8', 'format' => 'fixed'], $section->getField( 'Content-Type' )?->getAttributes() );
		self::assertSame( 'Grüße.txt', $section->getField( 'Content-Disposition' )?->getAttribute( 'filename' ) );
	}

	/**
	 *	Fields are split: white space before colon, several fields of same name,
	 *	address fields decoded name by name.
	 */
	public function testParseSplitsFields(): void
	{
		$header	= "To : =?UTF-8?B?".base64_encode( 'Doe, Jürgen' )."?= <a@example.org>\r\n"
			."Received: from a\r\n"
			."Received: from b\r\n"
			."Cc: =?UTF-8?Q?Z=C3=B6e?= <c@example.org>";
		$section	= Parser::getInstance()->parse( $header );
		self::assertSame( '"Doe, Jürgen" <a@example.org>', $section->getField( 'To' )?->getValue() );
		self::assertSame( 'Zöe <c@example.org>', $section->getField( 'Cc' )?->getValue() );
		$received	= array_map( static fn( $field ) => $field->getValue(), $section->getFieldsByName( 'Received' ) );
		self::assertSame( ['from a', 'from b'], $received );
	}

	/**
	 *	Fields with invalid names (non-ASCII, control characters) are skipped with their folded lines,
	 *	since they could not be rendered again.
	 */
	public function testParseSkipsInvalidFieldNames(): void
	{
		$header	= "Subject: x\r\nX-Bewertung\xFC: 3\r\n continued\r\nX-Valid: yes";
		$section	= Parser::getInstance()->parse( $header );
		self::assertSame( ['Subject', 'X-Valid'], array_map( static fn( $field ) => $field->getName(), $section->getFields() ) );
		self::assertSame( 'x', $section->getField( 'Subject' )?->getValue() );
		self::assertSame( 'yes', $section->getField( 'X-Valid' )?->getValue() );
	}

	/**
	 *	Folded lines are joined by one space, also if indented deeper (no words glued together).
	 */
	public function testParseUnfoldsWithSpace(): void
	{
		$header	= "Subject: Hello\r\n dear\r\n   friend\r\nTo: a@example.org,\r\n  b@example.org,\r\n\tc@example.org";
		$section	= Parser::getInstance()->parse( $header );
		self::assertSame( 'Hello dear friend', $section->getField( 'Subject' )?->getValue() );
		self::assertSame( 'a@example.org, b@example.org, c@example.org', $section->getField( 'To' )?->getValue() );
	}

	/**
	 *	RFC 2231: parameter names case-insensitive, unknown charset without loss.
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedHeaderValueCaseAndCharset(): void
	{
		$object	= Parser::parseAttributedHeaderValue( "attachment; FILENAME*=UTF-8''Gr%C3%BC%C3%9Fe.pdf; Size=3" );
		self::assertSame( ['filename' => 'Grüße.pdf', 'size' => '3'], $object->getAttributes() );

		$object	= Parser::parseAttributedHeaderValue( "attachment; filename*=x-unknown''Gr%C3%BC%C3%9Fe.pdf" );
		self::assertSame( ['filename' => 'Grüße.pdf'], $object->getAttributes(), 'valid UTF-8 kept' );

		$object	= Parser::parseAttributedHeaderValue( "attachment; filename*=x-unknown''Gr%FC%DFe.pdf" );
		self::assertSame( ['filename' => 'Grüße.pdf'], $object->getAttributes(), 'read as Latin-1' );
	}

	/**
	 *	Long parameter values (e.g. 100 KB quoted) do not exhaust PCRE stack: parameters are not lost.
	 *	@covers		::parseAttributedHeaderValue
	 */
	public function testParseAttributedHeaderValueLong(): void
	{
		$long	= str_repeat( 'a', 100000 );
		$value	= Parser::parseAttributedHeaderValue( 'text/plain; name="'.$long.'"; charset=utf-8' );
		self::assertSame( 'text/plain', $value->getValue() );
		self::assertSame( ['name' => $long, 'charset' => 'utf-8'], $value->getAttributes() );
	}

	/**
	 *	Bare CR (not followed by LF) in foreign header becomes a space: parsed message can be rendered again.
	 *	@covers		::parse
	 */
	public function testParseBareCarriageReturn(): void
	{
		$section	= Parser::getInstance()->parse( "X-Foo: a\rb\r\nSubject: x\r\r\n" );
		self::assertSame( 'a b', $section->getField( 'X-Foo' )?->getValue() );
		self::assertSame( "X-Foo: a b\r\nSubject: x ", \CeusMedia\Mail\Message\Header\Renderer::render( $section, TRUE ) );
	}
}
