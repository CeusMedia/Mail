<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Mail;
use CeusMedia\Mail\Mailbox\Search;
use CeusMedia\Mail\Message;

//  --  CONFIGURE !  -------------------------------------------------------  //

$limit		= 1;
$offset		= 0;

$mode		= 0;				//  mail IDs only
$mode		= 1;				//  fetch raw mail header
$mode		= 2;				//  fetch raw mail header and parse, displaying from string
$mode		= 3;				//  fetch raw mail header and parse, displaying from array
$mode		= 4;				//  fetch raw mail header and parse to Message (without body parts)
$mode		= 5;				//  fetch full raw mail and parse to Message (with body parts)

$verbose	= TRUE;

//  --  NO CHANGE NEEDED BELOW  --------------------------------------------  //

$configMailbox	= (object) $config->getAll( 'mailbox_' );
$configSended	= (object) $config->getAll( 'sending_' );

$mailbox		= connectMailbox( $configMailbox );

$search		= new Search();
$search->setLimit( $limit );
$search->setOffset( $offset );
//$search->setSubject( 'This is just a test' );
$search->setSender( $configSended->senderAddress );

$clock		= new Alg_Time_Clock();
$verbose && println( 'Criteria: '.$search->renderCriteria() );
$verbose && print( 'Performing search ...' );
$mailIds	= $mailbox->performSearch( $search );
$verbose && print( PHP_EOL );
$verbose && println( 'Found: '.count( $mailIds ).' mails' );
$verbose && println( 'Time needed: '.$clock->stop( 3, 0 ).' ms' );

$clock	= new Alg_Time_Clock();
foreach( $mailIds as $mailId => $mail ){
	println( str_pad( '--  #'.$mailId.'  ', '76', '-', STR_PAD_RIGHT ) );
	if( ( $mode ?? 0 ) === 0 ){
		println( $mail->getId() );
	}
	else if( $mode === 1 ){
		println( $mail->getRawHeader() );
	}
	else if( $mode === 2 ){
		println( $mail->getHeader()->toString() );
	}
	else if( $mode === 3 ){
		foreach( $mail->getHeader()->toArray() as $field ){
			println( '- '.$field );
		}
	}
	else if( $mode === 4 ){
		$message	= $mail->getMessage( FALSE );
		println( 'Subject: '.$message->getSubject() );
		println( 'Sender: '.$message->getSender()->get() );
		println( 'Headers:' );
		foreach( $message->getHeaders()->toArray() as $line ){
			println( '- '.$line );
		}
	}
	else if( $mode === 5 ){
		$message	= $mail->getMessage( TRUE );
		println( 'Subject: '.$message->getSubject() );
		println( 'Sender: '.$message->getSender()->get() );
		if( $message->hasText() ){
			println( 'Text Part Content:' );
			println( $message->getText()->getContent() );
		}
	}
	println( str_repeat( '-', 76 ) );
}
$verbose && println( 'Time needed: '.$clock->stop( 3, 0 ).' ms' );



//  --  FUNCTIONS  ---------------------------------------------------------  //

function connectMailbox( object $config ): Mailbox
{
	if( !$config->address )
		die( 'Error: No mailbox address defined.' );
	if( !$config->username )
		die( 'Error: No mailbox user name defined.' );
	if( !$config->password )
		die( 'Error: No mailbox user password defined.' );
	$mailbox	= new Mailbox(
		$config->host,
		$config->username,
		$config->password
	);
	$mailbox->setSecure( TRUE, TRUE );
	$mailbox->connect();
	return $mailbox;
}

function println( $line )
{
	print( $line.PHP_EOL );
}
