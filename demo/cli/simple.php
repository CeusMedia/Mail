<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.');

error_reporting( E_ALL );

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Transport\SMTP;

if(!file_exists("../config.ini"))
	die('Please copy "config.ini.dist" to "config.ini" and configure it.');
$config		= (object) parse_ini_file("../config.ini");

$verbose	= !TRUE;

if( getEnv( 'HTTP_HOST' ) )
	print '<xmp>';

SMTP::getInstance($config->host, $config->port)
	->setAuth($config->username, $config->password)
	->setVerbose($verbose)
	->send(Message::getInstance()
		->setSubject(sprintf($config->subject, uniqid()))
		->setSender($config->senderAddress, $config->senderName)
		->addRecipient($config->receiverAddress, $config->receiverName)
		->addText(sprintf($config->body, time()), "UTF-8", "quoted-printable")
	);
