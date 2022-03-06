<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Integration\Util;

use CeusMedia\Mail\Util\MX;
use CeusMedia\Mail\Test\TestCase;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_Mail
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Util\MX
 */
class MXTest extends TestCase
{
	public function setUp(): void
	{
		$this->mx	= MX::getInstance();
	}

	/**
	 *	@covers		::fromAddress
	 */
	public function testFromAddress()
	{
		$expected	= [10 => 'mail.itflow.de'];
		$actual		= $this->mx->fromAddress( 'christian.wuerker@ceusmedia.de' );
		$this->assertEquals( $expected, $actual );

		$expected	= [
			5	=> 'gmail-smtp-in.l.google.com',
			10	=> 'alt1.gmail-smtp-in.l.google.com',
			20	=> 'alt2.gmail-smtp-in.l.google.com',
			30	=> 'alt3.gmail-smtp-in.l.google.com',
			40	=> 'alt4.gmail-smtp-in.l.google.com',
		];
		$actual		= $this->mx->fromAddress( 'john.doe@gmail.com' );
		$this->assertEquals( $expected, $actual );

		$expected	= [5 => 'outlook-com.olc.protection.outlook.com'];
		$actual		= $this->mx->fromAddress( 'hans.testmann@outlook.com' );
		$this->assertEquals( $expected, $actual );

		$expected	= [10 => 'mx-aol.mail.gm0.yahoodns.net'];
		$actual		= $this->mx->fromAddress( 'hans.testmann@aol.com' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::fromHostname
	 */
	public function testFromHostname()
	{
		$expected	= [10 => 'mail.itflow.de'];
		$actual		= $this->mx->fromHostname( 'ceusmedia.de' );
		$this->assertEquals( $expected, $actual );

		$expected	= [
			5	=> 'gmail-smtp-in.l.google.com',
			10	=> 'alt1.gmail-smtp-in.l.google.com',
			20	=> 'alt2.gmail-smtp-in.l.google.com',
			30	=> 'alt3.gmail-smtp-in.l.google.com',
			40	=> 'alt4.gmail-smtp-in.l.google.com',
		];
		$actual		= $this->mx->fromHostname( 'gmail.com' );
		$this->assertEquals( $expected, $actual );
	}
}
