<?php
/**
 *	...
 *	@category		Test
 *	@package		CeusMedia_MailTest_Unit_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Address;

use CeusMedia\MailTest\TestCase;
use CeusMedia\Mail\Address\Name;

/**
*	...
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Name
 */
class NameTest extends TestCase
{
	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$firstname	= 'Firstname';
		$surname	= 'SURNAME';
		$fullname	= $surname.' '.$firstname;

		$name	= new Name();
		self::assertNull( $name->getFullname() );
		self::assertNull( $name->getFirstname() );
		self::assertNull( $name->getSurname() );

		$name	= new Name( $fullname );
		self::assertEquals( $fullname, $name->getFullname() );
		self::assertNull( $name->getFirstname() );
		self::assertNull( $name->getSurname() );

		$name	= new Name( $fullname, $surname, $firstname );
		self::assertEquals( $fullname, $name->getFullname() );
		self::assertEquals( $firstname, $name->getFirstname() );
		self::assertEquals( $surname, $name->getSurname() );
	}

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
			self::assertEquals( $expected[$nr], Name::splitNameParts( $name ) );
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
			self::assertEquals( $expected[$nr], Name::swapCommaSeparatedNameParts( $name ) );
	}

	/**
	*	@covers		::getFirstname
	*	@covers		::setFirstname
	 */
	public function testGetSetFirstname()
	{
		$name	= new Name();
		self::assertNull( $name->getFirstname() );

		$firstname	= 'Hans Dieter';
		$name->setFirstname( $firstname );
		self::assertEquals( $firstname, $name->getFirstname() );
	}

	/**
	*	@covers		::getFullname
	*	@covers		::setFullname
	 */
	public function testGetSetFullname()
	{
		$name	= new Name();
		self::assertNull( $name->getFullname() );

		$fullname	= 'Hans Testmann';
		$name->setFullname( $fullname );
		self::assertEquals( $fullname, $name->getFullname() );
	}

	/**
	*	@covers		::getSurname
	*	@covers		::setSurname
	 */
	public function testGetSetSurname()
	{
		$name	= new Name();
		self::assertNull( $name->getSurname() );

		$surname	= 'Testmann';
		$name->setSurname( $surname );
		self::assertEquals( $surname, $name->getSurname() );
	}
}
