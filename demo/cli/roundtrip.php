<?php
require_once __DIR__.'/_bootstrap.php';

use CeusMedia\Mail\Client;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;

$mode	= 0;				//  send message
$mode	= 1;				//  try to find/read sent message
//$mode	= 2;				//  both -> round trip

$configSmtp		= (object) $config->getAll( 'SMTP_' );
$configTest		= (object) $config->getAll( 'sending_' );
$configImap		= (object) $config->getAll( 'mailbox_' );

$client	= Client::getInstance()
	->setTransport(SMTP::getInstance(
		$configSmtp->host,
		$configSmtp->port,
		$configSmtp->username,
		$configSmtp->password
	))
	->setMailbox(Mailbox::getInstance(
		$configImap->host,
		$configImap->username,
		$configImap->password
	));

if( $mode === 0 || $mode === 2 ){
	$message	= $client->createMessage()
		->setSender( $configTest->senderAddress, $configTest->senderName )
		->addRecipient( $configTest->receiverAddress, $configTest->receiverName )
		->setSubject( "This is just a test" )
		->addText( "Test Message: ".date( 'r' ) );
	$client->sendMessage($message);
}
else if( $mode === 1 || $mode === 2 ){
	$search	= $client->createSearch()
		->setSender( $configTest->senderAddress )
		->setSubject( "This is just a test" )
		->setLimit( 1 )
		->setOffset( 0 );
	$mails	= $client->search( $search );
	if( $mails ){
		$mail	= array_pop( $mails );
		print_m( $mail->getHeader()->toArray() );
	}
}
