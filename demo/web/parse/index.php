<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message\Parser;
use \CeusMedia\Mail\Message\Renderer;
use \UI_HTML_Tag as Tag;

new UI_DevOutput;

$fileName		= "mail.txt";

if( !file_exists( $fileName ) )
	die( "Add a mail.txt or configure another file name in script!" );

$mail	= file_get_contents( $fileName );
$object	= Parser::getInstance()->parse( $mail );
$output	= Renderer::getInstance()->render( $object );
if( getEnv( 'HTTP_HOST' ) ){
	$page	= new UI_HTML_PageFrame();
	$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
	$page->addBody( Tag::create( 'div', array(
		Tag::create( 'div', array(
			Tag::create( 'h3', 'Original' ),
			Tag::create( 'pre', $mail ),
		), array( 'class' => 'span6' ) ),
		Tag::create( 'div', array(
			Tag::create( 'h3', 'Parsed and rendered' ),
			Tag::create( 'pre', $output ),
		), array( 'class' => 'span6' ) ),
	), array( 'class' => 'row-fluid' ) ) );
	print( $page->build() );
}
else{
	remark( "ORIGINAL:" );
	xmp( $mail );
	remark( "PARSED AND RENDERED:" );
	xmp( $output );
}
