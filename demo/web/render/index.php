<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Transport\SMTP;
use UI_HTML_PageFrame as Page;
use UI_HTML_Tag as Tag;

$configSend	= (object) $config->getAll( 'sending_' );
$configSmtp	= (object) $config->getAll( 'SMTP_' );

$configSend->subject	= "CeusMedia/Mail: Rendering Demo";
$configSend->body		= "There should be a blue block at the left.";

$html	= new Page();
$html->setTitle( $configSend->subject );
$html->addBody( Tag::create( 'div', array(
	Tag::create( 'img', NULL, array(
		'src'	=> 'CID:image1',
		'style'	=> 'width: 20px; height: 20px;'
	) ),
	Tag::create( 'span', $configSend->body ),
), array( 'class' => 'mail' ) ) );

$message	= new Message();
$message->setSubject( $configSend->subject );
$message->setSender( $configSend->senderAddress, $configSend->senderName );
$message->addRecipient( $configSend->receiverAddress, $configSend->receiverName );
$message->addHTML( $html->build() );
$message->addInlineImage( 'image1', '1x1-3094d7bf.png' );
//$message->addText( "TEXT" );
//$message->addFile( '1x1-3094d7bf.png' );

if( $configSmtp->username && $configSmtp->password ){
	$transport	= new SMTP( $configSmtp->host, $configSmtp->port );
	if( $configSend->senderAddress && $configSend->receiverAddress ){
		$transport->setAuth( $configSmtp->username, $configSmtp->password );
		$transport->send( $message );
	}
}

$output	= Renderer::getInstance()->render( $message );
xmp( $output );
