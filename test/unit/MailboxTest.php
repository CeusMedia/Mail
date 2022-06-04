<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit;

use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Mailbox
 */
class MailboxTest extends TestCase
{
	/**
	 *	@covers		::getInstance
	 */
	public function testGetInstance()
	{
		$connection	= new Connection( 'host.server.tld' );
		$instance	= Mailbox::getInstance( $connection );
		$this->assertEquals( new Mailbox( $connection ), $instance );
	}
}
