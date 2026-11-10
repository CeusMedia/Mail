<?php
/**
 *	Unit test for Transport/Result.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Transport
 */

namespace CeusMedia\MailTest\Unit\Transport;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Transport\Result;
use CeusMedia\MailTest\TestCase;
use RangeException;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\Result
 */
class ResultTest extends TestCase
{
	/**
	 *	@covers		::getStatus
	 */
	public function testDefaultStatus(): void
	{
		$result	= new Result();
		self::assertSame( Result::STATUS_UNKNOWN, $result->getStatus() );
	}

	/**
	 *	@covers		::setStatus
	 */
	public function testSetStatus(): void
	{
		$result	= new Result();
		self::assertSame( $result, $result->setStatus( Result::STATUS_SUCCESS ) );
		self::assertSame( Result::STATUS_SUCCESS, $result->getStatus() );
	}

	/**
	 *	@covers		::setStatus
	 */
	public function testSetStatusRejectsInvalidValue(): void
	{
		$result	= new Result();
		$this->expectException( RangeException::class );
		$result->setStatus( 'invalid' );
	}

	/**
	 *	@covers		::setStatus
	 */
	public function testSetStatusAllValidValues(): void
	{
		$result	= new Result();
		foreach( Result::STATUSES as $status ){
			$result->setStatus( $status );
			self::assertSame( $status, $result->getStatus() );
		}
	}

	/**
	 *	@covers		::setCode
	 *	@covers		::getCode
	 */
	public function testSetCode(): void
	{
		$result	= new Result();
		self::assertSame( $result, $result->setCode( 250 ) );
		self::assertSame( 250, $result->getCode() );
	}

	/**
	 *	@covers		::setMessage
	 *	@covers		::getMessage
	 */
	public function testSetMessage(): void
	{
		$result	= new Result();
		self::assertSame( $result, $result->setMessage( 'OK' ) );
		self::assertSame( 'OK', $result->getMessage() );
	}

	/**
	 *	@covers		::setId
	 *	@covers		::getId
	 */
	public function testSetId(): void
	{
		$result	= new Result();
		self::assertSame( $result, $result->setId( 42 ) );
		self::assertSame( 42, $result->getId() );
		self::assertSame( $result, $result->setId( 'abc' ) );
		self::assertSame( 'abc', $result->getId() );
		self::assertSame( $result, $result->setId( NULL ) );
		self::assertNull( $result->getId() );
	}

	/**
	 *	@covers		::setReceiver
	 *	@covers		::getReceiver
	 */
	public function testSetReceiver(): void
	{
		$result	= new Result();
		$address	= new Address( 'test@example.com' );
		self::assertSame( $result, $result->setReceiver( $address ) );
		self::assertSame( $address, $result->getReceiver() );

		$result->setReceiver( 'string@example.com' );
		self::assertSame( 'string@example.com', $result->getReceiver() );
	}
}
