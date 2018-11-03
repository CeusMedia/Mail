<?php
(@include '../../../vendor/autoload.php') or die('Please use composer to install required packages.');

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Parser;
use \CeusMedia\Mail\Message\Part\Texts;

if( getEnv( 'HTTP_HOST' ) )
	die( "CLI access, only." );

new UI_DevOutput;
error_reporting( E_ALL );

$content	= \FS_File_Reader::load( "mail.txt" );
$message	= Parser::parse( $content );
foreach( $message->getParts() as $part ){
	remark( "Part: ".get_class( $part ) );
	if( $part instanceof \CeusMedia\Mail\Message\Part\Text ){
		remark( " - Encoding: ".$part->getEncoding() );
		print_m( $part->getContent() );
	}
}
print( PHP_EOL );
