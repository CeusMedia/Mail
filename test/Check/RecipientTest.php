<?php
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Check
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Check_RecipientTest extends PHPUnit_Framework_TestCase{

	public function setUp(){
	}

	public function tearUp(){
	}

	public function testTest(){
		$participant	= new \CeusMedia\Mail\Participant( "office@ceusmedia.de" );
		$check			= new \CeusMedia\Mail\Check\Recipient( $participant );
		$creation		= $check->test( $participant );
		$this->assertEquals( TRUE, $creation );
	}
}
