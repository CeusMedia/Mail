<?php
(php_sapi_name() == 'cli' ) or die('Access denied: CLI only.');
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages!' . PHP_EOL);

//  --  PLEASE CONFIGURE!  --  //

$receiverAddress	= "";
$verbose			= TRUE;

// --  NO CHANGES NEEDED BELOW  --  //

(strlen( trim( $receiverAddress ) ) ) or die('Please configure receiver in script!' . PHP_EOL);

$config		= getConfig();
$body		= "This is just a test. The current UNIX timestamp is ".time();
$mail       = new \CeusMedia\Mail\Message();
$mail->setSender( $config->senderAddress, $config->senderName );
$mail->addRecipient( $receiverAddress, $config->receiverName );
$mail->setSubject( sprintf( $config->subject, uniqid() ) );
$mail->addPart( new \CeusMedia\Mail\Message\Part\HTML( $body ) );

$transport  = new \CeusMedia\Mail\Transport\SMTP( $config->host, $config->port );
$transport->setUsername( $config->username );
$transport->setPassword( $config->password );
$transport->setVerbose( $verbose );
$transport->send( $mail );

function getConfig(){
	if(!file_exists("config.ini"))
		die('Please copy "config.ini.dist" to "config.ini" and configure it.');
	$config		= (object) parse_ini_file( "config.ini" );
	return $config;
}
