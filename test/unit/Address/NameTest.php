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
			new Name( 'Hans Testmann' ),
			new Name( ' Hans  Testmann ' ),
			new Name( 'Firstname Surname' ),
			new Name( 'SURNAME Firstname' ),
			new Name( 'invalid' ),
		];
		$expected	= [
			(new Name( 'Hans Testmann' ))->setFirstname( 'Hans' )->setSurname( 'Testmann' ),
			(new Name( 'Hans Testmann' ))->setFirstname( 'Hans' )->setSurname( 'Testmann' ),
			(new Name( 'Firstname Surname' ))->setFirstname( 'Firstname' )->setSurname( 'Surname' ),
			(new Name( 'SURNAME Firstname' ))->setFirstname( 'Firstname' )->setSurname( 'Surname' ),
			(new Name( 'invalid' )),
		];
		foreach( $names as $nr => $name )
			$this->assertEquals( $expected[$nr], Name::splitNameParts( $names[$nr] ) );
	}

	/**
	 *	@covers		::swapCommaSeparatedNameParts
	 */
	public function testSwapCommaSeparatedNameParts()
	{
		$names	= [
			new Name( 'Testmann, Hans' ),
			new Name( 'invalid' ),
		];
		$expected	= [
			new Name( 'Hans Testmann' ),
			new Name( 'invalid' ),
		];
		foreach( $names as $nr => $name )
			$this->assertEquals( $expected[$nr], Name::swapCommaSeparatedNameParts( $name ) );
	}
}
