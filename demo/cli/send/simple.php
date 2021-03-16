<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Transport\SMTP;

$smtp		= (object) $config->getAll( 'SMTP_' );
$sending	= (object) $config->getAll( 'sending_' );

//  --  PLEASE CONFIGURE!  --  //

$verbose	= TRUE;
//$sending->receiverAddress	= "";

// --  NO CHANGES NEEDED BELOW  --  //

if( getEnv( 'HTTP_HOST' ) )
	print '<xmp>';

try{
	SMTP::getInstance( $smtp->host, $smtp->port )
		->setVerbose( $verbose )
		->setAuth( $smtp->username, $smtp->password )
		->send( Message::getInstance()
			->setSubject( sprintf( $sending->subject, uniqid() ) )
			->setSender( $sending->senderAddress, $sending->senderName )
			->addRecipient( $sending->receiverAddress, $sending->receiverName )
			->addText( sprintf( $sending->body, time() ), "UTF-8", "quoted-printable" )
		);
}
catch( Exception $e ){
	CLI::out( $e->getMessage() );
}
if( $verbose )
	CLI::out();
