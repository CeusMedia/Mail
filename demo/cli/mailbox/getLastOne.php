<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;

$mailboxAccess	= (object) $config->getAll( 'mailbox_' );

$verbose	= !TRUE;

if( !$mailboxAccess->address )
	die( 'Error: No mailbox address defined.' );
if( !$mailboxAccess->username )
	die( 'Error: No mailbox user name defined.' );
if( !$mailboxAccess->password )
	die( 'Error: No mailbox user password defined.' );
$connection	= new Connection(
	$mailboxAccess->host,
	$mailboxAccess->username,
	$mailboxAccess->password
);
$connection->setSecure( TRUE, TRUE );
//$mailbox->connect();

$mailbox	= new Mailbox( $connection );
$mailIndex	= array_slice( $mailbox->index(), 1, 1 );
foreach( $mailIndex as $item ){
	$message	= $mailbox->getMailAsMessage( $item );
	if( $message->hasText() )
		print_m( $message->getText()->getContent() );
	else
		print( 'Latest mail has no text part.' );
}
