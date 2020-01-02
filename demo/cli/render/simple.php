<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Transport\SMTP;

$message	= new Message();
$message->setSubject( '[Prefix] Subject' );
$message->setSender( 'dev@ceusmedia.de' );
$message->addText( 'Test: '.time() );
//$message->addHtml( '<b>Test:</b> <em>'.time().'</em>' );
$message->addRecipient( 'test2@ceusmedia.de' );

$raw	= Renderer::render( $message );
print( $raw );

$smtp	= new SMTP( 'mail.itflow.de', 587, 'kriss@ceusmedia.de', 'dialog' );
$smtp->send( $message );
