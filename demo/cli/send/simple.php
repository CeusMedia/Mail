<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\CLI;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;

/** @var Dictionary $config */

/** @var object{host: ?string, port: ?int, username: ?string, password: ?string} $smtp */
$smtp		= (object) $config->getAll( 'SMTP_' );

/** @var object{senderAddress: ?string, senderName: ?string, receiverAddress: ?string, receiverName: ?string, subject: ?string, body: ?string} $sending */
$sending	= (object) $config->getAll( 'sending_' );

//  --  PLEASE CONFIGURE!  --  //

$verbose	= TRUE;
//$sending->receiverAddress	= "";

// --  NO CHANGES NEEDED BELOW  --  //

if( getenv( 'HTTP_HOST' ) )
	print '<xmp>';

try{
	SMTP::getInstance( $smtp->host ?? '', $smtp->port ?? 25 )
		->setVerbose( $verbose )
		->setAuth( $smtp->username ?? '', $smtp->password ?? '' )
		->send( Message::getInstance()
			->setSubject( sprintf( $sending->subject ?? '', uniqid() ) )
			->setSender( $sending->senderAddress ?? '', $sending->senderName ?? '' )
			->addRecipient( $sending->receiverAddress ?? '', $sending->receiverName ?? '' )
			->addText( sprintf( $sending->body ?? '', time() ), "UTF-8", "quoted-printable" )
		);
}
catch( SmtpException $e ){
	CLI::out();
	CLI::out( 'Error: '.$e->getMessage() );
	CLI::out( print_m( $e->getResponse(), NULL, NULL, TRUE ) );
}
catch( Exception $e ){
	CLI::out();
	CLI::out( 'Error: '.$e->getMessage() );
}
if( $verbose )
	CLI::out();
