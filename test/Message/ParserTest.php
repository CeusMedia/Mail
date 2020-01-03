<?php
/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( __DIR__ ).'/bootstrap.php';

use CeusMedia\Mail\Message\Parser;

/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Parser
 */
class Message_ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 */
	public function testParse(){
		$this->markTestIncomplete( 'No test defined for Message\\Parser' );

		$parser	= Parser::getInstance();
	}
}
