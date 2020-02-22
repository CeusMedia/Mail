<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Message\Renderer;
use UI_HTML_PageFrame as Page;
use UI_HTML_Tag as Tag;

new UI_DevOutput;

$fileName	= "../../mails/01-simple-7bit";
//$fileName	= "../../mails/02-simple-umlauts";
//$fileName	= "../../mails/03-simple-printable";
//$fileName	= "../../mails/04-simple-base64";
//$fileName	= "../../mails/05-simple-attachment";

if( !file_exists( $fileName ) )
	die( "Add a mail.txt or configure another file name in script!" );
if( !getEnv( 'HTTP_HOST' ) )
	die( "This demo is for browser, only" );

$mail	= file_get_contents( $fileName );
$object	= Parser::getInstance()->parse( $mail );
$parts	= $object->getParts( TRUE );

$listMain	= array();
addListItem( $listMain, 'Subject', $object->getSubject() );
addListItem( $listMain, 'Date', current( $object->getHeaders()->getFieldsByName( 'Date' ) )->getValue() );
addListItem( $listMain, 'From', $object->getSender()->get() );
foreach( $object->getRecipients() as $type => $recipients ){
	foreach( $recipients as $recipient ){
		addListItem( $listMain, ucfirst( $type ), $recipient->get() );
	}
}
$headerReturnPath	= $object->getHeaders()->getFieldsByName( 'Return-Path' );
$headerDeliveredTo	= $object->getHeaders()->getFieldsByName( 'Delivered-To' );
if( $headerDeliveredTo )
	addListItem( $listMain, 'Delivered-To', $headerDeliveredTo[0]->getValue() );
if( $headerReturnPath )
	addListItem( $listMain, 'Return', $headerReturnPath[0]->getValue() );
addListItem( $listMain, 'ID', current( $object->getHeaders()->getFieldsByName( 'Message-ID' ) )->getValue() );
$listMain		= UI_HTML_Tag::create( 'ul', $listMain );

$listParts		= array();
foreach( $parts as $nr => $part ){
	$list2		= array();
	$listParts[]	= UI_HTML_Tag::create( 'h4', 'Part #'.( $nr + 1 ) );
	addListItem( $list2, 'Type', Alg_Object_Constant::staticGetKeyByValue( '\CeusMedia\Mail\Message\Part', $part->getType(), 'TYPE_' ) );
	if( $part->getCharset() )
		addListItem( $list2, 'Charset', $part->getCharset() );
	if( $part->getEncoding() )
		addListItem( $list2, 'Encoding', $part->getEncoding() );
	if( $part->getFormat() )
		addListItem( $list2, 'Format', $part->getFormat() );
	addListItem( $list2, 'Class', get_class( $part ) );
	if( $part->isInlineImage() )
		addListItem( $list2, 'Content-ID', $part->getId() );
	if( $part->isAttachment() ){
		if( $part->getFileName() )
			addListItem( $list2, 'Filename', $part->getFileName() );
		if( $part->getFileSize() )
			addListItem( $list2, 'Filesize', $part->getFileSize() );
	}
	$listParts[]	= UI_HTML_Tag::create( 'ul', $list2 );
	if( $part->isText() ){
		$listParts[]	= UI_HTML_Tag::create( 'h5', 'Content' );
		$listParts[]	= UI_HTML_Tag::create( 'xmp', $part->getContent() );
	}
	else if( $part->isHTML() ){
		$listParts[]	= UI_HTML_Tag::create( 'h5', 'Content' );
		$listParts[]	= UI_HTML_Tag::create( 'xmp', $part->getContent() );
	}
}
$listParts	= join( $listParts );

$listMore		= array();
foreach( $object->getHeaders()->getFields() as $header ){
	if( in_array( $header->getName(), array( 'Date', 'From', 'To', 'Subject', 'Message-ID', 'Return-Path', 'Delivered-To' ) ) )
		continue;
	if( preg_match( '/^X-/', $header->getName() ) )
		continue;
	if( !strlen( trim( $header->getValue() ) ) )
		continue;
	addListItem( $listMore, $header->getName(), $header->getValue() );
}
$listMore		= UI_HTML_Tag::create( 'ul', $listMore );

$listExtended	= array();
foreach( $object->getHeaders()->getFields() as $header ){
	if( !preg_match( '/^X-/', $header->getName() ) )
		continue;
	if( !strlen( trim( $header->getValue() ) ) )
		continue;
	addListItem( $listExtended, $header->getName(), $header->getValue() );
}
$listExtended		= UI_HTML_Tag::create( 'ul', $listExtended );

$page	= new Page();
$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
$page->addBody( Tag::create( 'div', array(
	Tag::create( 'div', array(
		Tag::create( 'h2', 'Source' ),
		Tag::create( 'pre', htmlentities( $mail, ENT_QUOTES, 'UTF-8' ) ),
	), array( 'class' => 'span5' ) ),
	Tag::create( 'div', array(
		Tag::create( 'h2', 'Parsed' ),
		Tag::create( 'div', array(
			Tag::create( 'h3', 'Main Headers' ),
			Tag::create( 'div', $listMain ),
			Tag::create( 'h3', 'Parts' ),
			Tag::create( 'div', $listParts ),
			Tag::create( 'h3', 'More Headers' ),
			Tag::create( 'div', $listMore ),
			Tag::create( 'h3', 'Extended Headers' ),
			Tag::create( 'div', $listExtended ),
		) ),
	), array( 'class' => 'span7' ) ),
), array( 'class' => 'row-fluid' ) ) );
print( $page->build() );

function addListItem( & $list, $key, $value ){
	$value	= htmlentities( $value, ENT_QUOTES, 'UTF-8' );;
	$list[]	= UI_HTML_Tag::create( 'li', $key.': '.$value );
}
