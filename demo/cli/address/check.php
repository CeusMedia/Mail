<?php

use CeusMedia\Common\UI\DevOutput;
use CeusMedia\Mail\Address\Check\Availability as AvailabilityCheck;

require_once dirname( __DIR__ ).'/_bootstrap.php';

new DevOutput;

$s	= "dev@ceusmedia.de";
$c	= new AvailabilityCheck( $s, TRUE );

print( 'SMTP communication:'.PHP_EOL );
try{
	$result	= $c->test( $s );
}
catch( Throwable|Exception ){
	$result	= FALSE;
}
print( PHP_EOL );
print( 'Result: '.( $result ? 'Success' : 'Fail' ).PHP_EOL );

$response	= $c->getLastResponse();
if( 0 !== $response->error ){
	print( PHP_EOL );
	print( 'Error:'.PHP_EOL );
	print( '- Code:    '.$response->code.PHP_EOL );
	print( '- Message: '.$response->message.PHP_EOL );
}
