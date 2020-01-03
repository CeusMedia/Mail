<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message;

$configSend	= (object) $config->getAll( 'sending_' );
$configSmtp	= (object) $config->getAll( 'SMTP_' );

$configSend->subject	= "CeusMedia/Mail: Rendering Demo";
$configSend->body		= "There should be a blue block at the left.";

$html		= new UI_HTML_PageFrame();
$html->setTitle( $configSend->subject );
$html->addBody( UI_HTML_Tag::create( 'div', array(
	UI_HTML_Tag::create( 'img', NULL, array(
		'src'	=> 'CID:image1',
		'style'	=> 'width: 20px; height: 20px;'
	) ),
	UI_HTML_Tag::create( 'span', $configSend->body ),
), array( 'class' => 'mail' ) ) );

$message	= new Message();
$message->setSubject( $configSend->subject );
$message->addHtml( $html->build() );
$message->addInlineImage( 'image1', '1x1-3094d7bf.png' );
//$message->addText( "TEXT" );
//$message->addFile( '1x1-3094d7bf.png' );

if( $configSmtp->username && $configSmtp->password ){
	if( $configSend->senderAddress && $configSend->receiverAddress ){
		$message->setSender( $configSend->senderAddress, $configSend->senderName );
		$message->addRecipient( $configSend->receiverAddress, $configSend->receiverName );
		$transport	= new \CeusMedia\Mail\Transport\SMTP( $configSmtp->host, $configSmtp->port );
		$transport->setAuth( $configSmtp->username, $configSmtp->password );
		$transport->send( $message );
	}
}

$output	= \CeusMedia\Mail\Message\Renderer::getInstance()->render( $message );
xmp( $output );
