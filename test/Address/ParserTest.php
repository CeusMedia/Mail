<?php
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail address parser.
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Address_ParserTest extends PHPUnit_Framework_TestCase
{

	/**
	 *	@expectedException		InvalidArgumentException
	 */
	public function testParseException(){
		$parser	= new \CeusMedia\Mail\Address\Parser();
		$parser->parse( 'invalid' );
	}
}
