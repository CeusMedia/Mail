<?php

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\CLI;
use CeusMedia\Mail\Message as Message;
use CeusMedia\Mail\Message\Part\HTML as MessageHtmlPart;
use CeusMedia\Mail\Transport\SMTP;

require_once dirname( __DIR__ ).'/_bootstrap.php';

/** @var Dictionary $config */

/** @var object{host: ?string, port: ?int, username: ?string, password: ?string} $smtp */
$smtp		= (object) $config->getAll( 'SMTP_' );

/** @var object{senderAddress: ?string, senderName: ?string, receiverAddress: ?string, receiverName: ?string, subject: ?string, body: ?string} $sending */
$sending	= (object) $config->getAll( 'sending_' );

//  --  PLEASE CONFIGURE!  --  //

$verbose			= TRUE;
//$sending->receiverAddress	= "";

// --  NO CHANGES NEEDED BELOW  --  //

if( 0 === strlen( trim( $sending->receiverAddress ?? '' ) ) )
	die( 'Please configure receiver in script!'.PHP_EOL );

try{
	$body		= "This is just a test. The current UNIX timestamp is ".time();
	$mail		= new Message();
	$mail->setSender( $sending->senderAddress ?? '', $sending->senderName ?? '' );
	$mail->addRecipient( $sending->receiverAddress ?? '', $sending->receiverName ?? '' );
	$mail->setSubject( sprintf( $sending->subject, uniqid() ) );
	$mail->addPart( new MessageHtmlPart( $body ) );

	$transport  = new SMTP( $smtp->host ?? '', $smtp->port ?? 25 );
	$transport->setUsername( $smtp->username ?? '' );
	$transport->setPassword( $smtp->password ?? '' );
	$transport->setVerbose( $verbose );
	$transport->send( $mail );

	if( $verbose )
		CLI::out();
}
catch( Exception $e ){
	print( 'Error: '.$e->getMessage().PHP_EOL );
	print( 'Trace: '.PHP_EOL );
	print( ' - '.str_replace( "\n", "\n - ", $e->getTraceAsString() ).PHP_EOL );
	exit;
}
