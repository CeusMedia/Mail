<?php
/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Message;

use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Parser' );

		$parser	= Parser::getInstance();
	}
}
