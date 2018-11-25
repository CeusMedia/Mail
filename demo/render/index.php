<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);
new UI_DevOutput;

error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );

if(!file_exists( 'config.ini' ) )
	die('Please copy "config.ini.dist" to "config.ini" and configure it.');
$config		= (object) parse_ini_file( 'config.ini' );


$html		= new UI_HTML_PageFrame();
$html->setTitle( $config->subject );
$html->addBody( UI_HTML_Tag::create( 'div', array(
	UI_HTML_Tag::create( 'img', NULL, array( 'src' => 'CID:image1', 'style' => 'width: 20px; height: 20px;' ) ),
	UI_HTML_Tag::create( 'span', $config->body ),
), array( 'class' => 'mail' ) ) );

$message	= new \CeusMedia\Mail\Message();
$message->addHtml( $html->build() );
//$message->addText( "TEXT" );
$message->addInlineImage( 'image1', '1x1-3094d7bf.png' );
//$message->addFile( '1x1-3094d7bf.png' );
$message->setSubject( $config->subject );

if( $config->username && $config->password ){
	if( $config->senderAddress && $config->receiverAddress ){
		$message->setSender( $config->senderAddress, $config->senderName );
		$message->addRecipient( $config->receiverAddress, $config->receiverName );
		$transport	= new \CeusMedia\Mail\Transport\SMTP( $config->host, $config->port );
		$transport->setAuth( $config->username, $config->password );
		$transport->send( $message );
	}
}

$output	= \CeusMedia\Mail\Message\Renderer::render( $message );
xmp( $output );
