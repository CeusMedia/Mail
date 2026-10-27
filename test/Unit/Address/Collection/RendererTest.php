<?php
/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Address\Collection;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Address\Collection\Renderer;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address collection parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Address_Collection
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Address\Collection\Renderer
 */
class RendererTest extends TestCase
{
	protected $renderer;

	public function setup(): void
	{
		$this->renderer	= new Renderer();
//		parent::__construct();
	}

	/**
	 *	@covers		::create
	 *	@covers		::getInstance
	 *	@todo		remove coverage of create after removing method
	 */
	public function testGetInstance()
	{
		$instance	= Renderer::getInstance();

		self::assertEquals( $this->renderer, $instance );
	}

	/**
	 *	@covers		::getDelimiter
	 *	@covers		::setDelimiter
	 */
	public function testSetDelimiter()
	{
		foreach( $delimiters = ['-', '#'] as $delimiter ){
			$this->renderer->setDelimiter( $delimiter );
			self::assertEquals( $delimiter, $this->renderer->getDelimiter() );
		}
	}

	/**
	 *	@covers		::isEncodingNames
	 *	@covers		::setEncodeNames
	 *	@covers		::getLineLength
	 *	@covers		::setLineLength
	 */
	public function testSetEncodeNamesAndLineLength()
	{
		self::assertFalse( $this->renderer->isEncodingNames() );
		self::assertSame( 0, $this->renderer->getLineLength() );

		self::assertSame( $this->renderer, $this->renderer->setEncodeNames()->setLineLength( 76 ) );
		self::assertTrue( $this->renderer->isEncodingNames() );
		self::assertSame( 76, $this->renderer->getLineLength() );

		self::assertSame( 0, $this->renderer->setLineLength( -1 )->getLineLength() );
	}

	/**
	 *	For headers: names with special characters are quoted and escaped, others encoded (RFC 2047).
	 *	@covers		::render
	 *	@covers		::render
	 */
	public function testRenderEncodedNames()
	{
		$collection	= new AddressCollection( [
			new Address( 'one@example.com' ),
			( new Address( 'two@example.com' ) )->setName( 'Test Name' ),
			( new Address( 'three@example.com' ) )->setName( 'Doe, John' ),
			( new Address( 'four@example.com' ) )->setName( 'Max "Mo" Muster' ),
			( new Address( 'five@example.com' ) )->setName( 'Zoë' ),
		] );
		$actual	= $this->renderer->setEncodeNames()->render( $collection );

		$expected	= 'one@example.com, Test Name <two@example.com>, "Doe, John" <three@example.com>, '
			.'"Max \\"Mo\\" Muster" <four@example.com>, ';
		self::assertStringStartsWith( $expected, $actual );
		self::assertMatchesRegularExpression( '/, =\?UTF-8\?B\?.+\?= <five@example\.com>$/', $actual );

		//  without encoding, as before: names quoted, but not encoded
		self::assertStringEndsWith( '"Zoë" <five@example.com>', $this->renderer->setEncodeNames( FALSE )->render( $collection ) );
	}

	/**
	 *	Non-ASCII names are encoded as a whole, so special characters stay inside the encoded words.
	 *	Long names are split into several encoded words of whole characters.
	 *	@covers		::render
	 *	@covers		::render
	 */
	public function testRenderEncodedNamesWithSpecialCharacters()
	{
		$collection	= new AddressCollection( [
			( new Address( 'one@example.com' ) )->setName( 'Doe, Jürgen' ),
			( new Address( 'two@example.com' ) )->setName( str_repeat( 'Jürgen ', 20 ).'Müller' ),
		] );
		$actual	= $this->renderer->setEncodeNames()->setLineLength( 76 )->render( $collection );

		self::assertStringStartsWith( '=?UTF-8?B?'.base64_encode( 'Doe, Jürgen' ).'?= <one@example.com>,', $actual );
		foreach( explode( Message::$delimiter, $actual ) as $line ){
			self::assertLessThanOrEqual( 76, strlen( $line ) );
			self::assertMatchesRegularExpression( '/^[\x20-\x7E]*$/', $line );
		}
		$decoded	= iconv_mime_decode( 'To: '.$actual, 0, 'UTF-8' );
		self::assertSame( 'To: Doe, Jürgen <one@example.com>, '.str_repeat( 'Jürgen ', 20 ).'Müller <two@example.com>', $decoded );
	}

	/**
	 *	Folding between addresses, lines start with space, delimiter stays at line end.
	 *	@covers		::render
	 */
	public function testRenderFolded()
	{
		$addresses	= [];
		for( $i = 1; $i <= 10; $i++ )
			$addresses[]	= new Address( 'receiver-number-'.$i.'@example.com' );
		$collection	= new AddressCollection( $addresses );

		$actual	= $this->renderer->setLineLength( 76 )->render( $collection );
		$lines	= explode( "\r\n", $actual );
		self::assertGreaterThan( 1, count( $lines ) );
		foreach( $lines as $nr => $line ){
			self::assertLessThanOrEqual( 76, strlen( $line ) );
			if( 0 !== $nr )
				self::assertStringStartsWith( ' receiver-number-', $line );
			if( $nr < count( $lines ) - 1 )
				self::assertStringEndsWith( ',', $line );
		}
		self::assertSame( $this->renderer->setLineLength( 0 )->render( $collection ), preg_replace( '/\r\n /', ' ', $actual ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender1()
	{
		$collection		= new AddressCollection( array(
			new Address( 'dev@ceusmedia.de' ),
		) );
		$expected	= 'dev@ceusmedia.de';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender2()
	{
		$collection		= new AddressCollection( array(
			new Address( '<dev@ceusmedia.de>' ),
		) );

		$expected	= 'dev@ceusmedia.de';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender3()
	{
		$collection		= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
		) );

		$expected	= 'Developer <dev@ceusmedia.de>';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender4()
	{
		$collection		= new AddressCollection( array(
			new Address( '"Developer" <dev@ceusmedia.de>' ),
		) );

		$expected	= 'Developer <dev@ceusmedia.de>';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender5()
	{
		$collection		= new AddressCollection( array(
			new Address( '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>' ),
		) );
		$expected	= '"Developer (Dev-Crew)" <dev.dev-crew@ceusmedia.de>';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender6()
	{
		$collection		= new AddressCollection( array(
			new Address( 'Developer <dev@ceusmedia.de>' ),
			new Address( 'Alpha Tester <test@ceusmedia.de>' ),
		) );
		$expected	= 'Developer <dev@ceusmedia.de>, "Alpha Tester" <test@ceusmedia.de>';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender7()
	{
		$collection		= new AddressCollection( array() );
		$expected	= '';
		self::assertEquals( $expected, $this->renderer->render( $collection ) );
	}

	/**
	 *	@covers		::setDelimiter
	 */
	public function testSetDelimiterException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$this->renderer->setDelimiter( '' );
	}

	/**
	 *	Offset (header name) is respected: lines with encoded words stay within 76 characters,
	 *	an address is moved to the next line instead of being split, if it fits there.
	 *	@covers		::setOffset
	 *	@covers		::getOffset
	 *	@covers		::render
	 */
	public function testRenderWithOffset(): void
	{
		self::assertSame( 0, $this->renderer->getOffset() );
		self::assertSame( 10, $this->renderer->setOffset( 10 )->getOffset() );

		$collection	= new AddressCollection( [
			( new Address( 'a@example.com' ) )->setName( 'Bob' ),
			( new Address( 'c.very.long.address@subdomain.example.com' ) )->setName( str_repeat( 'Müller ', 12 ) ),
			( new Address( 'd@example.com' ) )->setName( 'Zoë' ),
		] );
		$actual	= $this->renderer->setEncodeNames()->setLineLength( 76 )->render( $collection );
		$lines	= explode( Message::$delimiter, 'Reply-To: '.$actual );
		foreach( $lines as $line )
			self::assertLessThanOrEqual( 76, strlen( $line ), $line );
		self::assertSame( ' =?UTF-8?B?'.base64_encode( 'Zoë' ).'?= <d@example.com>', end( $lines ) );

		$decoded	= Encoding::getInstance()->decodeAddressListIfNeeded( $actual );
		self::assertSame( 'Bob <a@example.com>, '.str_repeat( 'Müller ', 12 ).'<c.very.long.address@subdomain.example.com>, Zoë <d@example.com>', $decoded );
	}

	/**
	 *	Without line length, long encoded names are not folded either.
	 *	@covers		::render
	 */
	public function testRenderEncodedWithoutFolding(): void
	{
		$name		= str_repeat( 'Jürgen ', 12 ).'Müller';
		$collection	= new AddressCollection( [( new Address( 'a@example.com' ) )->setName( $name )] );
		$actual		= $this->renderer->setEncodeNames()->render( $collection );
		self::assertStringNotContainsString( "\n", $actual );
		self::assertSame( $name.' <a@example.com>', Encoding::getInstance()->decodeAddressListIfNeeded( $actual ) );
	}
}
