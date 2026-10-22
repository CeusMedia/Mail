<?php
/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

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
	 *	@todo		test giving strategies, too! (needs access to protected $strategy)
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
		foreach( [Parser::STRATEGY_OWN, Parser::STRATEGY_ICONV] as $strategy ){
			$section	= Parser::getInstance( $strategy )->parse( $header );
			self::assertSame( $expected, $section->getField( 'Subject' )?->getValue(), 'strategy '.$strategy );
			self::assertSame( '1.0', $section->getField( 'MIME-Version' )?->getValue() );
		}
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
}
