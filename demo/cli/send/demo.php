<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

$smtp		= (object) $config->getAll( 'SMTP_' );
$sending	= (object) $config->getAll( 'sending_' );

//  --  PLEASE CONFIGURE!  --  //

$verbose			= TRUE;
//$sending->receiverAddress	= "";

// --  NO CHANGES NEEDED BELOW  --  //

(strlen( trim( $sending->receiverAddress ) ) ) or die('Please configure receiver in script!' . PHP_EOL);
try{
	$body		= "This is just a test. The current UNIX timestamp is ".time();
	$mail       = new \CeusMedia\Mail\Message();
	$mail->setSender( $sending->senderAddress, $sending->senderName );
	$mail->addRecipient( $sending->receiverAddress, $sending->receiverName );
	$mail->setSubject( sprintf( $sending->subject, uniqid() ) );
	$mail->addPart( new \CeusMedia\Mail\Message\Part\HTML( $body ) );

	$transport  = new \CeusMedia\Mail\Transport\SMTP( $smtp->host, $smtp->port );
	$transport->setUsername( $smtp->username );
	$transport->setPassword( $smtp->password );
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
