<?php
(php_sapi_name() == 'cli' ) or die('Access denied: CLI only.');
(@include '../vendor/autoload.php') or die('Please use composer to install required packages!' . PHP_EOL);

/*
$mail		= file_get_contents( 'parse.txt' );
$message	= CeusMedia\Mail\Parser::parse( $mail );
print_r( $message->getParts() );
*/
