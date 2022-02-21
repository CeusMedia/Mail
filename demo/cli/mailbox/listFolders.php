<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;

$configMailbox	= (object) $config->getAll( 'mailbox_' );

$mailbox		= connectMailbox( $configMailbox );

print( 'Connecting...' );
print( PHP_EOL );

print( 'Reading folders...' );
$folders	= $mailbox->getFolders( TRUE, TRUE );

print( PHP_EOL );
print( 'Folders:'.PHP_EOL );
foreach( $folders as $folder )
	print( '- '.$folder.PHP_EOL );


//  --  FUNCTIONS  --  //

function connectMailbox( $config ){
	if( !$config->address )
		die( 'Error: No mailbox address defined.' );
	if( !$config->username )
		die( 'Error: No mailbox user name defined.' );
	if( !$config->password )
		die( 'Error: No mailbox user password defined.' );
	return new Mailbox( new Connection(
		$config->host,
		$config->username,
		$config->password,
		TRUE,
		TRUE
	) );
}
