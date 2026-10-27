<?php
/**
 *	Sends a message by Transport\Local and prints results as JSON.
 *	Runs in own process, since sendmail_path can only be set on startup.
 *	Usage: php -d sendmail_path="..." send.php SUBJECT [bcc-only]
 */
require_once dirname( __DIR__, 4 ).'/vendor/autoload.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\Local;
use CeusMedia\Mail\Transport\Result;

$message	= Message::getInstance()
	->setSender( 'sender@example.org', 'Jürgen Müller' )
	->addRecipient( 'to1@example.org' )
	->addRecipient( 'to2@example.org', 'Zoë Empfänger' )
	->addRecipient( 'copy@example.org', NULL, 'cc' )
	->addRecipient( 'hidden@example.org', NULL, 'bcc' )
	->addHeaderPair( 'Bcc', 'hidden@example.org' )								//  like a parsed message, must not be doubled
	->setSubject( $argv[1] ?? 'Test' )
	->addText( "Grüße\r\n.mit Punkt" );

if( 'bcc-only' === ( $argv[2] ?? '' ) )
	$message	= Message::getInstance()
		->setSender( 'sender@example.org' )
		->addRecipient( 'hidden@example.org', NULL, 'bcc' )
		->setSubject( $argv[1] ?? 'Test' )
		->addText( 'Hallo' );

$results	= ( new Local() )->send( $message );
print( json_encode( array_map( static fn( Result $result ) => [
	'receiver'	=> $result->getReceiver()->getAddress(),
	'status'	=> $result->getStatus(),
	'message'	=> $result->getMessage(),
], $results ) ) );
