<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

$smtp		= (object) $config->getAll( 'SMTP_' );
$sending	= (object) $config->getAll( 'sending_' );

new UI_DevOutput;

/*  PREPARATION  */
$verbose		= !TRUE;

$sender			= "dev@ceusmedia.de";
$receiverTo		= "dev@ceusmedia.de";
$receiverCc		= "test1@ceusmedia.de";
$receiverBcc	= "test2@ceusmedia.de";

$subject		= "Test - ".date("Y-m-d H:i:s");
$bodyText		= "Test Message: ".date("Y-m-d H:i:s");
$bodyHtml		= '<html>
	<head>
		<style>'.file_get_contents("https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css").'</style>
		<style>html,body,#wrapper{height:100%}#wrapper{background-color:#EEE;box-sizing:box-model;padding:2em}.container{padding:2em;background-color:#FFF;border:1px solid #BBB}</style>
	</head>
	<body>
		<div id="wrapper">
			<div class="container">
				<div class="jumbotron">
					<h1><img src="CID:logo"/>&nbsp;Test Message</h1>
				</div>
				<p>This is just a test message sent by CeusMedia/Mail.</p>
			</div>
		</div>
	</body>
</html>';

/*  EXECUTION  */
try {
	//  prepare SMTP transport
	$transport	= new \CeusMedia\Mail\Transport\SMTP($smtp->host, $smtp->port);		//  create SMTP transport
	$transport->setUsername($smtp->username);										//  set SMTP auth username
	$transport->setPassword($smtp->password);										//  set SMTP auth password
	$transport->setVerbose($verbose);												//  toggle verbosity - you can remove this line

/*	//  check if receiver exists on server
	$check		= new \CeusMedia\Mail\Address\Check\Availability($sender);						//  create checker for receiver
	$check->setVerbose($verbose);													//  toggle verbosity - you can remove this line
	if (!$check->test($receiverTo)) {												//  receiver is not existing
		$error	= $check->getLastError();
		print_m( $error );
//		print "Receiver <".$receiverTo."> is not existing.";
//		exit;
	}*/

	//  create message
	$message	= new \CeusMedia\Mail\Message();									//  create mail message object
	$message->setSender($sender);													//  set sender
	$message->addRecipient($receiverTo);											//  set TO receiver
	$message->addRecipient($receiverCc, NULL, 'cc');								//  set CC receiver
	$message->addRecipient($receiverBcc, NULL, 'bcc' );								//  set BCC receiver
	$message->setSubject($subject);													//  set mail subject
	$message->addText($bodyText);													//  set mail content as plain text part
	$message->addHTML($bodyHtml);													//  set mail content as HTML part
	$message->addInlineImage('logo', __DIR__.'/../../files/test.png');				//  add inline image
	$message->addAttachment(__DIR__."/../../../README.md", 'text/markdown' );
	$message->setReadNotificationRecipient($sender);

	//  send message
	$transport->send($message);														//  send message via prepared transport
}
catch (Exception $e) {
	print(PHP_EOL.'Exception: '.$e->getMessage());
}
