<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Parser;
use \CeusMedia\Mail\Message\Part\Text;
use \CeusMedia\Mail\Message\Part\Attachment;

$mailFile		= "mail.txt";

$content	= \FS_File_Reader::load( __DIR__."/".$mailFile );
$message	= Parser::create()->parse( $content );

foreach( $message->getParts() as $nr => $part ){
	remark( "Part #".($nr + 1) );
	if( $part instanceof Text ){
		remark( " - Class:    ".get_class( $part ) );
		remark( " - Encoding: ".$part->getEncoding() );
		remark( " - Content:  ".$part->getContent() );
	}
	else if( $part instanceof Attachment ){
		remark( " - Class:    ".get_class( $part ) );
		remark( " - Encoding: ".$part->getEncoding() );
		remark( " - MimeType: ".$part->getMimeType() );
		remark( " - Content:  ".strlen( $part->getContent() ).' Bytes' );
	}
}

foreach( $message->getHeaders()->getFields() as $headerField ){
	print( '- '.$headerField->toString().PHP_EOL );
}

print( PHP_EOL );
