<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Parser;
use \CeusMedia\Mail\Message\Part\Text;

$content	= \FS_File_Reader::load( "mail.txt" );
$message	= Parser::parse( $content );
foreach( $message->getParts() as $part ){
	remark( "Part: ".get_class( $part ) );
	if( $part instanceof Text ){
		remark( " - Encoding: ".$part->getEncoding() );
		print_m( $part->getContent() );
	}
}
print( PHP_EOL );
