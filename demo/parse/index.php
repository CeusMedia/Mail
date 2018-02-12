<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);
new UI_DevOutput;

$fileName		= "mail.txt";

if( !file_exists( $fileName ) )
	die( "Add a mail.txt or configure another file name in script!" );
error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
$mail	= file_get_contents( 'mail.txt' );
$object	= \CeusMedia\Mail\Message\Parser::parse( $mail );
$output	= \CeusMedia\Mail\Message\Renderer::render( $object );
if( getEnv( 'HTTP_HOST' ) ){
	$page	= new UI_HTML_PageFrame();
	$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
	$page->addBody( UI_HTML_Tag::create( 'div', array(
		UI_HTML_Tag::create( 'div', array(
			UI_HTML_Tag::create( 'h3', 'Original' ),
			UI_HTML_Tag::create( 'pre', $mail ),
		), array( 'class' => 'span6' ) ),
		UI_HTML_Tag::create( 'div', array(
			UI_HTML_Tag::create( 'h3', 'Parsed and rendered' ),
			UI_HTML_Tag::create( 'pre', $output ),
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
