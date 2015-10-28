<?php
(@include '../vendor/autoload.php') or die('Please use composer to install required packages.');

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Part\Text;
use \CeusMedia\Mail\Transport\SMTP;

if(!file_exists("config.ini"))
	die('Please copy "config.ini.dist" to "config.ini" and configure it.');

$config	= array_merge(parse_ini_file("config.ini"), array(
	'port'				=> 587,
	'receiverAddress'	=> "dev@ceusmedia.de",
	'receiverName'		=> "Ceus Media Developer",
));

if( getEnv( 'HTTP_HOST' ) )
	print '<xmp>';

SMTP::getInstance($config['host'], $config['port'])
	->setUsername($config['username'])
	->setPassword($config['password'])
	->setSecure($config['port'] != 25)
	->setVerbose(TRUE)
	->send(Message::getInstance()
		->setSender($config['senderAddress'], $config['senderName'])
		->addRecipient($config['receiverAddress'], $config['receiverName'])
		->setSubject(sprintf($config['subject'], uniqid()))
		->addPart(new Text(sprintf($config['body'], time()), "UTF-8", "quoted-printable"))
	);
