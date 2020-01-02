<?php
require_once __DIR__.'/../../../vendor/autoload.php';
new UI_DevOutput;

$s	= "dev@ceusmedia.de";
$c	= new CeusMedia\Mail\Address\Check\Availability( $s, TRUE );

print( 'SMTP communication:'.PHP_EOL );
$result	= $c->test( $s );
print( PHP_EOL );
print( 'Result: '.( $result ? 'Success' : 'Fail' ).PHP_EOL );

$error	= $c->getLastError();
if( $error->error ){
	print( PHP_EOL );
	print( 'Error:'.PHP_EOL );
	print( '- Code:    '.$error->code.PHP_EOL );
	print( '- Message: '.$error->message.PHP_EOL );
}
