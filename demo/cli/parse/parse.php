<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser;

$fileName	= "../../mails/01-simple-7bit";
//$fileName	= "../../mails/02-simple-umlauts";
//$fileName	= "../../mails/03-simple-printable";
//$fileName	= "../../mails/04-simple-base64";
//$fileName	= "../../mails/05-simple-attachment";

$showParts		= TRUE;
$showHeaders	= TRUE;

$content	= \FS_File_Reader::load( $fileName );
$message	= Parser::getInstance()->parse( $content );

if( $showParts ){
	remark( 'Parts:' );
	foreach( $message->getParts() as $nr => $part ){
		remark( "- Part #".($nr + 1) );
		if( $part->isText() ){
			remark( "  - Class:    ".get_class( $part ) );
			remark( "  - Encoding: ".$part->getEncoding() );
			#		remark( " - Content:  ".$part->getContent() );
		}
		else if( $part->isAttachment() ){
			remark( "  - Class:    ".get_class( $part ) );
			remark( "  - Filename: ".$part->getFileName() );
			remark( "  - Encoding: ".$part->getEncoding() );
			remark( "  - MimeType: ".$part->getMimeType() );
			remark( "  - Content:  ".strlen( $part->getContent() ).' Bytes' );
		}
		else if( $part->isInlineImage() ){
			remark( "  - Class:    ".get_class( $part ) );
			remark( "  - Filename: ".$part->getFileName() );
			remark( "  - Encoding: ".$part->getEncoding() );
			remark( "  - MimeType: ".$part->getMimeType() );
			remark( "  - Content:  ".strlen( $part->getContent() ).' Bytes' );
		}
	}
	remark();
}

if( $showHeaders ){
	remark( 'Headers:' );
	foreach( $message->getHeaders()->getFields() as $headerField ){
		remark( '- '.$headerField->toString() );
	}
}

print( PHP_EOL );
