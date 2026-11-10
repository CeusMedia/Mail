<?php
/**
 *	Unit test for Library.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit
 */

namespace CeusMedia\MailTest\Unit;

use CeusMedia\Mail\Library;
use CeusMedia\MailTest\TestCase;
use ReflectionClass;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Library
 */
class LibraryTest extends TestCase
{
	/**
	 *	Reset the static $detected flag before each test so detect() runs fresh.
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$ref	= new ReflectionClass( Library::class );
		$detected	= $ref->getProperty( 'detected' );
		$detected->setAccessible( TRUE );
		$detected->setValue( null, FALSE );
	}

	/**
	 *	@covers		::getIdentifier
	 */
	public function testGetIdentifier(): void
	{
		$identifier	= Library::getIdentifier();
		self::assertNotNull( $identifier );
		self::assertIsString( $identifier );
		self::assertNotEmpty( $identifier );
	}

	/**
	 *	@covers		::getVersion
	 */
	public function testGetVersion(): void
	{
		$version	= Library::getVersion();
		self::assertNotNull( $version );
		self::assertIsString( $version );
		self::assertNotEmpty( $version );
	}

	/**
	 *	@covers		::getUserAgent
	 */
	public function testGetUserAgent(): void
	{
		$agent	= Library::getUserAgent();
		self::assertMatchesRegularExpression( '/^.+\/.+$/', $agent );
		self::assertStringContainsString( '/', $agent );
	}

	/**
	 *	@covers		::getIdentifier
	 *	@covers		::getVersion
	 *	@covers		::getUserAgent
	 */
	public function testUserAgentCombinesIdentifierAndVersion(): void
	{
		$identifier	= Library::getIdentifier();
		$version	= Library::getVersion();
		$agent	= Library::getUserAgent();
		self::assertSame( $identifier.'/'.$version, $agent );
	}
}
