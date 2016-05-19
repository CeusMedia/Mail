<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.');

if(!file_exists("../config.ini"))
	die('Please copy "config.ini.dist" to "config.ini" and configure it.');

$config	= parse_ini_file("../config.ini");

/*  PREPARATION  */
$verbose		= !TRUE;

$smtpServer		= $config['host'];
$smtpPort		= $config['port'];
$smtpUsername	= $config['username'];
$smtpPassword	= $config['password'];

$sender			= "dev@ceusmedia.de";
$receiverTo		= "dev@ceusmedia.de";
$receiverCc		= "test1@ceusmedia.de";
$receiverBcc	= "test2@ceusmedia.de";

$subject		= "Test - ".date("Y-m-d H:i:s");
$bodyText		= "Test Message: ".date("Y-m-d H:i:s");
$bodyHtml		= '<h2>Test Message</h2><img src="CID:test1"/>';


/*  EXECUTION  */
try {
	//  prepare SMTP transport
	$transport	= new \CeusMedia\Mail\Transport\SMTP($smtpServer, $smtpPort);	//  create SMTP transport
	$transport->setUsername($smtpUsername);										//  set SMTP auth username
	$transport->setPassword($smtpPassword);										//  set SMTP auth password
	$transport->setVerbose($verbose);											//  toggle verbosity - you can remove this line

	//  check if receiver exists on server
	$check		= new \CeusMedia\Mail\Check\Recipient($sender);					//  create checker for receiver
	$check->setVerbose($verbose);												//  toggle verbosity - you can remove this line
	if (!$check->test($receiverTo)) {											//  receiver is not existing
		print "Receiver <".$receiverTo->getAddress()."> is not existing.";
		exit;
	}

	//  prepare attachment
	$attachment	= new \CeusMedia\Mail\Part\Attachment();
	$attachment->setFile("../../readme.md");

	//  create message
	$message	= new \CeusMedia\Mail\Message();									//  create mail message object
	$message->setSender($sender);													//  set sender
	$message->addRecipient($receiverTo);											//  set TO receiver
	$message->addRecipient($receiverCc, NULL, 'cc');								//  set CC receiver
	$message->addRecipient($receiverBcc, NULL, 'bcc' );								//  set BCC receiver
	$message->setSubject($subject);													//  set mail subject
	$message->addPart(new \CeusMedia\Mail\Part\Text($bodyText));					//  set mail content as plain text part
	$message->addPart(new \CeusMedia\Mail\Part\HTML($bodyHtml));					//  set mail content as HTML part
	$message->addPart(new \CeusMedia\Mail\Part\InlineImage('test1', 'test.png'));	//  add inline image
	$message->addPart($attachment);
	$message->setReadNotificationRecipient($sender);

	//  send message
	$transport->send($message);														//  send message via prepared transport
}
catch (Exception $e) {
	print(PHP_EOL.'Exception: '.$e->getMessage());
}
