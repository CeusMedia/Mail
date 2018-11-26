<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Transport\SMTP;

$verbose	= !TRUE;

if( getEnv( 'HTTP_HOST' ) )
	print '<xmp>';

$smtp		= (object) $config->getAll( 'SMTP_' );
$sending	= (object) $config->getAll( 'sending_' );

SMTP::getInstance( $smtp->host, $smtp->port )
	->setAuth( $smtp->username, $smtp->password )
	->setVerbose( $verbose )
	->send( Message::getInstance()
		->setSubject( sprintf( $sending->subject, uniqid() ) )
		->setSender( $sending->senderAddress, $sending->senderName )
		->addRecipient( $sending->receiverAddress, $sending->receiverName )
		->addText( sprintf( $sending->body, time() ), "UTF-8", "quoted-printable" )
	);
