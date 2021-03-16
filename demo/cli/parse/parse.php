<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser;

//  --  CONFIGuRE  ---------------------------------------------------------------------  //

$files		= [
	'../../mails/01-simple-7bit',
	'../../mails/02-simple-umlauts',
	'../../mails/03-simple-printable',
	'../../mails/04-simple-base64',
	'../../mails/05-simple-attachment',
	'../../mails/06-complex-folding',
];

$showParts			= TRUE;
$showHeaders		= TRUE;
$showDeliveryChain	= TRUE;

//  --  NO CHANGES NEEDED BELOW  -------------------------------------------------------  //

$fileNr		= $argv[1] ?? 0;
$fileName	= $files[$fileNr] ?? $files[0];

remark( 'File: '.array_reduce( preg_split( '@/@', $fileName ), function( $carry, $item ){return $item;} ) );
remark();

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
		if( $headerField->getName( FALSE ) !== 'Subject' )
			continue;
		remark( '- '.$headerField->toString() );
	}
	remark();
}

if( $showDeliveryChain ){
	remark( 'Delivery Chain:' );
	foreach( $message->getDeliveryChain() as $address ){
		remark( '- '.$address );
	}
	remark();
}

//print( PHP_EOL );
