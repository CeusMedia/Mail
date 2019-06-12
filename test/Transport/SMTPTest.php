<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( __DIR__ ).'/bootstrap.php';
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Transport_SMTPTest extends TestCase
{
	public function testSend(){
		$message	= \CeusMedia\Mail\Message::create()
			->addRecipient( 'receiver@muster-server.tld' )
			->setSender( 'sender@muster-server.tld' )
			->addText( 'Das ist ein <b>Test</b>.' )
			->setSubject( 'Das ist ein Test' );

//		print( \CeusMedia\Mail\Message\Renderer::render( $message ) );

		$smtp		= new \CeusMedia\Mail\Transport\SMTP( 'egal', 1 );			//  get SMTP instance
		$socket		= new SmtpSocketMock();										//  create mocking replacement for default SMTP socket
		$smtp->setSocket( $socket );											//  set mocking SMTP socket
		$smtp->setAuth( 'username', 'password' );								//  auth is supported by mock
		$smtp->setSecure( TRUE );												//  SSL is supported by mock

		try{
			$returned	= $smtp->send( $message );								//  try to send mail
			$this->assertTrue( is_object( $returned ) );

			$creation	= $returned instanceof \CeusMedia\Mail\Transport\SMTP;
			$this->assertTrue( $creation );
		}
		catch( Exception $e ){
//			print_r( $socket->getLog() );
			$this->fail( $e );
		}
	}
}

class SmtpSocketMock extends \CeusMedia\Mail\Transport\SMTP\Socket{

	protected $lastChunk;
	protected $nextResponses	= array();
	protected $status			= 0;
	protected $log				= array();

	public function __construct(){}

	public function close(){
		$this->connection	= FALSE;
	}

	public function enableCrypto( $enable, $crypto ){
		return TRUE;
	}

	protected function getFakeAnswer(){
		if( count( $this->nextResponses ) > 0 )
			return array_shift( $this->nextResponses );
		$last	= trim( $this->lastChunk );
		if( !$last || $last === "STARTTLS" )
			return [220, ""];
		if( $last === "DATA" )
			return [354, ""];
		if( $last === "QUIT" )
			return [221, ""];
		if( $last === "." )
			return [250, ""];
		if( $last === "AUTH LOGIN" ){
			$this->nextResponses	= array( [334, ""], [235, ""] );
			return [334, ""];
		}
		if( preg_match( '/^(EHLO|HELO|MAIL FROM:|RCPT TO:) /', $last ) )
			return [250, ""];
		return [0, "Unmocked request"];
	}

	public function getLog(){
		return $this->log;
	}

	public function open( $forceReopen = FALSE ){
		$this->connection	= TRUE;
	}

	public function readResponse( $length ){
		$response	= $this->getFakeAnswer();
		$raw		= $response[0].' '.$response[1];
//		print( PHP_EOL.' < '. $raw );
		$this->log[]	= trim( ' < '. $raw );
		return (object) array(
			'code'		=> $response[0],
			'message'	=> $response[1],
			'raw'		=> array( $raw ),
		);
	}

	public function sendChunk( $message ){
		$this->lastChunk	= $message;
//		print( PHP_EOL.' > '.$message );
		$this->log[]	= trim( ' > '. $message );
		return strlen( $message );
	}
}
