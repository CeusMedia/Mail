<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Transport\SMTP;

$configSmtp	= (object) $config->getAll( 'SMTP_' );

$message	= new Message();
$message->setSubject( '[Prefix] Subject' );
$message->setSender( $configSend->senderAddress );
$message->addText( 'Test: '.time() );
//$message->addHtml( '<b>Test:</b> <em>'.time().'</em>' );
$message->addRecipient( $configSend->receiverAddress, $configSend->receiverName );

$raw	= Renderer::render( $message );
print( $raw );

if( $configSmtp->username && $configSmtp->password ){
	if( $configSend->senderAddress && $configSend->receiverAddress ){
		$transport	= new SMTP( $configSmtp->host, $configSmtp->port );
		$transport->setAuth( $configSmtp->username, $configSmtp->password );
		$transport->send( $message );
	}
}
