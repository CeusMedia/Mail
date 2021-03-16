<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Mailbox;

$mailboxAccess	= (object) $config->getAll( 'mailbox_' );

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

print( 'Connecting...' );
$mailbox->setSecure( TRUE, TRUE );
$mailbox->connect();
print( PHP_EOL );

print( 'Reading folders...' );
$folders	= $mailbox->getFolders( TRUE, TRUE );

print( PHP_EOL );
print( 'Folders:'.PHP_EOL );
foreach( $folders as $folder )
	print( '- '.$folder.PHP_EOL );

$mailbox->disconnect();
