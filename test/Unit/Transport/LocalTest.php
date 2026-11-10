<?php
/**
 *	Unit test for Transport/Local parameter validation.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Transport
 */

namespace CeusMedia\MailTest\Unit\Transport;

use CeusMedia\Mail\Transport\Local;
use CeusMedia\MailTest\TestCase;
use InvalidArgumentException;
use ReflectionMethod;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\Local
 */
class LocalTest extends TestCase
{
	/**
	 *	Access the protected validateParameters method via reflection.
	 *	@return		ReflectionMethod
	 */
	protected function getValidateMethod(): ReflectionMethod
	{
		$method	= new ReflectionMethod( Local::class, 'validateParameters' );
		$method->setAccessible( TRUE );
		return $method;
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testEmptyParameters(): void
	{
		$method	= $this->getValidateMethod();
		$result	= $method->invoke( null, [] );
		self::assertSame( [], $result );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testAllowedFlagF(): void
	{
		$method	= $this->getValidateMethod();
		$result	= $method->invoke( null, ['-fsender@example.com'] );
		self::assertSame( ['-fsender@example.com'], $result );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testAllowedFlagCapitalF(): void
	{
		$method	= $this->getValidateMethod();
		$result	= $method->invoke( null, ['-FSenderName'] );
		self::assertSame( ['-FSenderName'], $result );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testAllowedFlagR(): void
	{
		$method	= $this->getValidateMethod();
		$result	= $method->invoke( null, ['-Rreply@example.com'] );
		self::assertSame( ['-Rreply@example.com'], $result );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testAllowedFlagLowerR(): void
	{
		$method	= $this->getValidateMethod();
		$result	= $method->invoke( null, ['-return-path@example.com'] );
		self::assertSame( ['-return-path@example.com'], $result );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testMultipleAllowedFlags(): void
	{
		$method	= $this->getValidateMethod();
		$result	= $method->invoke( null, ['-fsender@example.com', '-FSenderName'] );
		self::assertSame( ['-fsender@example.com', '-FSenderName'], $result );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testDisallowedFlag(): void
	{
		$method	= $this->getValidateMethod();
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Disallowed sendmail parameter flag: -o' );
		$method->invoke( null, ['-OQueueDirectory'] );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testUnsafeCharacters(): void
	{
		$method	= $this->getValidateMethod();
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unsafe sendmail parameter detected' );
		$method->invoke( null, ["-f; rm -rf /"] );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testCommandInjectionAttempt(): void
	{
		$method	= $this->getValidateMethod();
		$this->expectException( InvalidArgumentException::class );
		$method->invoke( null, ["-b.attacker@evil.com"] );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testNoDashPrefix(): void
	{
		$method	= $this->getValidateMethod();
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unsafe sendmail parameter detected' );
		$method->invoke( null, ['sender@example.com'] );
	}

	/**
	 *	@covers		::validateParameters
	 */
	public function testEmptyStringParameter(): void
	{
		$method	= $this->getValidateMethod();
		$this->expectException( InvalidArgumentException::class );
		$method->invoke( null, [''] );
	}
}
