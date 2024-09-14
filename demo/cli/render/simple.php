<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Transport\SMTP;

/** @var Dictionary $config */

$configSmtp	= (object) $config->getAll( 'SMTP_' );
$configSend	= (object) $config->getAll( 'sending_' );

$message	= new Message();
$message->setSubject( '[Prefix] Subject' );
$message->setSender( $configSend->senderAddress ?? '' );
//$message->addText( 'Test: '.time() );
$message->addHTML( '<b>Test:</b> <em>'.time().'</em>' );
$message->addRecipient( $configSend->receiverAddress ?? '', $configSend->receiverName ?? '' );

$raw	= Renderer::render( $message );
print( $raw );

if( $configSmtp->username ?? '' && $configSmtp->password ?? '' ){
	if( $configSend->senderAddress && $configSend->receiverAddress ?? '' ){
		$transport	= new SMTP( $configSmtp->host ?? '', $configSmtp->port ?? 25 );
		$transport->setAuth( $configSmtp->username, $configSmtp->password );
		$transport->send( $message );
	}
}
