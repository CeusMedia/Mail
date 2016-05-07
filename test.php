<?php
(@include '../../autoload.php') or die('Please use composer to install required packages.');

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Part\Text;
use \CeusMedia\Mail\Part\HTML;
use \CeusMedia\Mail\Part\Attachment;
use \CeusMedia\Mail\Transport\SMTP;

if(!file_exists("config.ini"))
	die('Please copy "config.ini.dist" to "config.ini" and configure it.');

$config	= array_merge(parse_ini_file("config.ini"), array(
	'port'				=> 587,
	'receiverAddress'	=> "admin@univerlag-leipzig.de",
	'receiverName'		=> "Ceus Media Developer",
));

if( getEnv( 'HTTP_HOST' ) )
	print '<xmp>';


$html	= '<html>
  <head>

    <meta http-equiv="content-type" content="text/html; charset=utf-8">
  </head>
  <body bgcolor="#FFFFFF" text="#000000">
    Das ist ein <b>TEST</b>.<br>
    <br>
    <br>
  </body>
</html>';

$mail	= Message::getInstance();
$mail->setSender($config['senderAddress'], $config['senderName']);
$mail->addRecipient($config['receiverAddress'], $config['receiverName']);
$mail->setSubject(sprintf($config['subject'], uniqid()));
$mail->addPart(new Text("Das ist ein *TEST*.", "UTF-8", "quoted-printable"));
$mail->addPart(new HTML($html, "UTF-8", "7bit"));
$attachment	= new Attachment();
$attachment->setFile("/home/kriss/Dokumente/Business/Signatur.txt");
$mail->addPart($attachment);

SMTP::getInstance($config['host'], $config['port'])
	->setUsername($config['username'])
	->setPassword($config['password'])
	->setSecure($config['port'] != 25)
	->setVerbose(TRUE)
	->send($mail);
