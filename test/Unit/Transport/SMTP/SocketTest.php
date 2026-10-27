<?php
/**
 *	Unit test for SMTP socket.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Transport_SMTP
 */

namespace CeusMedia\MailTest\Unit\Transport\SMTP;

use CeusMedia\Mail\Transport\SMTP\Response;
use CeusMedia\Mail\Transport\SMTP\Socket;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for SMTP socket.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Transport_SMTP
 *  @coversDefaultClass \CeusMedia\Mail\Transport\SMTP\Socket
 */
class SocketTest extends TestCase
{
	/**
	 *	Failed connection is reported by response, also if error message contains "%" (e.g. from host name).
	 *	@covers		::open
	 */
	public function testOpenFailureWithPercentInMessage(): void
	{
		$socket		= ( new Socket( 'bad%shost.invalid', 25 ) )->setTimeout( 3 );
		$response	= $socket->open();
		self::assertSame( Response::ERROR_SOCKET_FAILED, $response->getError() );
		self::assertStringStartsWith( 'Socket connection to SMTP server "bad%shost.invalid" at port 25 failed', (string) $response->getMessage() );
	}

	/**
	 *	QUIT on close is a complete command line, ending with CRLF (RFC 5321, 2.3.8).
	 *	@covers		::close
	 */
	public function testCloseSendsQuitWithLineEnding(): void
	{
		$file		= (string) tempnam( sys_get_temp_dir(), 'smtp' );
		$stream		= fopen( $file, 'wb' );
		self::assertIsResource( $stream );
		$socket		= new class( 'smtp.example.org', 25 ) extends Socket {
			/** @param resource $connection */
			public function setConnection( $connection ): void
			{
				$this->connection	= $connection;
			}
		};
		$socket->setConnection( $stream );
		$socket->close();
		self::assertFalse( $socket->isOpen() );
		self::assertSame( "QUIT\r\n", file_get_contents( $file ) );
		unlink( $file );
	}

	/**
	 *	Closing a connection, which the server closed already, gives no warning (broken pipe),
	 *	also not for error handlers turning warnings into exceptions.
	 *	@covers		::close
	 */
	public function testCloseConnectionClosedByServer(): void
	{
		$pair		= stream_socket_pair( STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP );
		self::assertIsArray( $pair );
		fclose( $pair[1] );															//  server side closed
		$socket		= new class( 'smtp.example.org', 25 ) extends Socket {
			/** @param resource $connection */
			public function setConnection( $connection ): void
			{
				$this->connection	= $connection;
			}
		};
		$socket->setConnection( $pair[0] );
		set_error_handler( static function( int $code, string $message ): never {
			throw new \ErrorException( $message, 0, $code );
		} );
		try{
			$socket->close();
		}
		finally{
			restore_error_handler();
		}
		self::assertFalse( $socket->isOpen() );
	}
}
