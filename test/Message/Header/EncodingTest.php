<?php
/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once __DIR__.'/bootstrap.php';

use \CeusMedia\Mail\Message\Header\Encoding;

/**
 *	Unit test for mail message.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Encoding
 */
class Message_Header_EncodingTest extends PHPUnit_Framework_TestCase
{
	/**
	 *	@covers		::decodeIfNeeded
	 */
	public function testDecodeIfNeeded(){
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
	public function testEncodeIfNeeded(){
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
	public function testEncodeIfNeededException(){
		$this->expectException( 'InvalidArgumentException' );
		Encoding::encodeIfNeeded( "ÄÖÜ", "_invalid_" );
	}
}
