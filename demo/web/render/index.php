<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\UI\HTML\PageFrame as Page;
use CeusMedia\Common\UI\HTML\Tag as Tag;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Transport\SMTP;

/** @var Dictionary $config */

$sendMail		= !TRUE;
$sendVerbose	= !TRUE;

$configSend	= (object) $config->getAll( 'sending_' );
$configSmtp	= (object) $config->getAll( 'SMTP_' );

$configSend->subject	= "CeusMedia/Mail: Rendering Demo";
$configSend->body		= "There should be a blue block at the left.";

//print_m($configSend);
//print_m($configSmtp);

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

if( $sendMail && $configSmtp->username && $configSmtp->password ){
	$transport	= new SMTP( $configSmtp->host, $configSmtp->port );
	$transport->setVerbose( $sendVerbose );
	if( $configSend->senderAddress && $configSend->receiverAddress ){
		$transport->setAuth( $configSmtp->username, $configSmtp->password );
		$transport->send( $message );
	}
}

$output	= Renderer::getInstance()->render( $message );
xmp( $output );
