<?php
/**
 *	...
 *	@category		Test
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\Mail\Test\Unit\Address;

use CeusMedia\Mail\Test\TestCase;
use CeusMedia\Mail\Address\Name;

/**
*	...
 *	@category			Test
 *	@package			CeusMedia_Mail_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Name
 */
class NameTest extends TestCase
{
	/**
	 *	@covers		::splitNameParts
	 */
	public function testSplitNameParts()
	{
		$names		= [
			['fullname' => 'Hans Testmann'],
			['fullname' => ' Hans  Testmann '],
			['fullname' => 'Firstname Surname'],
			['fullname' => 'SURNAME Firstname'],
			['fullname' => 'invalid'],
		];
		$expected	= [
			['fullname' => 'Hans Testmann', 'firstname' => 'Hans', 'surname' => 'Testmann'],
			['fullname' => 'Hans Testmann', 'firstname' => 'Hans', 'surname' => 'Testmann'],
			['fullname' => 'Firstname Surname', 'firstname' => 'Firstname', 'surname' => 'Surname'],
			['fullname' => 'SURNAME Firstname', 'firstname' => 'Firstname', 'surname' => 'Surname'],
			['fullname' => 'invalid'],
		];
		$actual		= Name::splitNameParts( $names );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 *	@covers		::swapCommaSeparatedNameParts
	 */
	public function testSwapCommaSeparatedNameParts()
	{
		$names	= [
			['fullname' => 'Testmann, Hans'],
			['fullname' => 'invalid'],
		];
		$expected	= [
			['fullname' => 'Hans Testmann'],
			['fullname' => 'invalid'],
		];
		$actual		= Name::swapCommaSeparatedNameParts( $names );
		$this->assertEquals( $expected, $actual );
	}
}
