<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Mailbox;

$mailboxAccess	= (object) $config->getAll( 'mailbox_' );

$verbose	= !TRUE;

if( !$mailboxAccess->address )
	die( 'Error: No mailbox address defined.' );
if( !$mailboxAccess->username )
	die( 'Error: No mailbox user name defined.' );
if( !$mailboxAccess->password )
	die( 'Error: No mailbox user password defined.' );
$mailbox	= new Mailbox(
	$mailboxAccess->host,
	$mailboxAccess->username,
	$mailboxAccess->password
);
$mailbox->setSecure( TRUE, TRUE );
$mailbox->connect();
$mailIndex	= array_slice( $mailbox->index(), 0, 3 );
foreach( $mailIndex as $item ){
	print_r( $mailbox->getMail( $item ) );
}
