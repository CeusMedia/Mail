<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Common\UI\HTML\PageFrame as Page;
use CeusMedia\Common\UI\HTML\Tag as Tag;

new CeusMedia\Common\UI\DevOutput;

/** @var array<string> $files */

$fileName	= array_keys( $files )[2];

if( !file_exists( $fileName ) )
	die( "Add a mail.txt or configure another file name in script!" );

$mail	= file_get_contents( $fileName );
$object	= Parser::getInstance()->parse( $mail );
$output	= Renderer::getInstance()->render( $object );
if( getEnv( 'HTTP_HOST' ) ){
	$page	= new Page();
	$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
	$page->addBody( Tag::create( 'div', array(
		Tag::create( 'div', array(
			Tag::create( 'h3', 'Original' ),
			Tag::create( 'pre', htmlentities( $mail, ENT_QUOTES, 'UTF-8' ) ),
		), array( 'class' => 'span6' ) ),
		Tag::create( 'div', array(
			Tag::create( 'h3', 'Parsed and rendered' ),
			Tag::create( 'pre', htmlentities( $output, ENT_QUOTES, 'UTF-8' ) ),
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
