<?php
/**
 *	Unit test for mail header field.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail recipient address validation.
 *	@category		Test
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Parser_AddressListTest extends PHPUnit_Framework_TestCase
{
	public function testParse1(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> '',
				'address'	=> 'dev@ceusmedia.de',
			),
		);
		$string		= 'dev@ceusmedia.de';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );

		$string		= ' dev@ceusmedia.de, ';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse2(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> '',
				'address'	=> 'dev@ceusmedia.de',
			),
		);
		$string		= '<dev@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );

		$string		= ', <dev@ceusmedia.de> ,';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse3(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Developer',
				'address'	=> 'dev@ceusmedia.de',
			),
		);
		$string		= 'Developer <dev@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse4(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Developer',
				'address'	=> 'dev@ceusmedia.de',
			),
		);
		$string		= '"Developer" <dev@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse5(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Developer, Tester',
				'address'	=> 'dev@ceusmedia.de',
			),
		);
		$string		= '"Developer, Tester" <dev@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse6(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Developer, Tester',
				'address'	=> 'dev@ceusmedia.de',
			),
		);
		$string		= '"Developer, Tester" <dev@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse7(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Developer (Dev-Crew)',
				'address'	=> 'dev.dev-crew@ceusmedia.de',
			),
		);
		$string		= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse8(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Developer',
				'address'	=> 'dev@ceusmedia.de',
			),
			array(
				'fullname'	=> 'Tester',
				'address'	=> 'test@ceusmedia.de',
			),
		);
		$string		= 'Developer <dev@ceusmedia.de>, Tester <test@ceusmedia.de>';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );

		$string		= ',Developer <dev@ceusmedia.de>,  "Tester"  <test@ceusmedia.de>, ';
		$creation	= $parser->parse( $string );
		$this->assertEquals( $assertion, $creation );
	}

	public function testParse9(){
		$parser		= new \CeusMedia\Mail\Parser\AddressList();

		$assertion	= array(
			array(
				'fullname'	=> 'Hans Testmann',
				'firstname'	=> 'Hans',
				'surname'	=> 'Testmann',
				'address'	=> 'hans.testmann@ceusmedia.de',
			),
		);
		$string		= '"Testmann, Hans" <hans.testmann@ceusmedia.de>';
		$creation	= $parser->parse( $string, TRUE, TRUE );
		$this->assertEquals( $assertion, $creation );
	}
}
