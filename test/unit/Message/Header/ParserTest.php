<?php
/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Message\Header;

use CeusMedia\Mail\Message\Header\Parser;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
		$this->markTestIncomplete( 'No test defined for Message\\Header\\Parser' );

		$parser	= Parser::getInstance();
	}
}
