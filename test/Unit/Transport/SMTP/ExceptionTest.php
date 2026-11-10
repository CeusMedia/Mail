<?php
/**
 *	Unit test for Transport/SMTP/Exception.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Transport_SMTP
 */

namespace CeusMedia\MailTest\Unit\Transport\SMTP;

use CeusMedia\Mail\Transport\SMTP\Exception;
use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\MailTest\TestCase;
use RuntimeException;

/**
 *	@coversDefaultClass	\CeusMedia\Mail\Transport\SMTP\Exception
 */
class ExceptionTest extends TestCase
{
	/**
	 *	Tests that Exception extends RuntimeException.
	 */
	public function testExtendsRuntimeException(): void
	{
		$ex	= new Exception( 'test error' );
		self::assertInstanceOf( RuntimeException::class, $ex );
		self::assertSame( 'test error', $ex->getMessage() );
	}

	/**
	 *	@covers		::getResponse
	 */
	public function testGetResponseIsNullByDefault(): void
	{
		$ex	= new Exception( 'test' );
		self::assertNull( $ex->getResponse() );
	}

	/**
	 *	@covers		::setResponse
	 *	@covers		::getResponse
	 */
	public function testSetAndGetResponse(): void
	{
		$ex	= new Exception( 'test' );
		$response	= new Response();
		$response->setCode( 550 );
		$response->setMessage( 'Mailbox unavailable' );

		self::assertSame( $ex, $ex->setResponse( $response ) );
		self::assertSame( $response, $ex->getResponse() );
		self::assertSame( 550, $ex->getResponse()->getCode() );
	}
}
