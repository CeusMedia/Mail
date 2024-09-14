<?php

use CeusMedia\Common\Net\HTTP\Request\Receiver as HttpRequestReceiver;

require_once dirname( __DIR__ ).'/_bootstrap.php';

$sending	= (object) $config->getAll( 'sending_' );

$request	= new HttpRequestReceiver;

$result	= '- no mail triggered -';
if( $request->has( 'send' ) && $request->get( 'receiverAddress' ) ){
	ob_start();
	try{
		sendMail( $config );
	}
	catch( Exception $e ){
		print "\n\nException: ".$e->getMessage()."\n".$e->getTraceAsString();
	}
	$result	= ob_get_clean();
}

$body	= '
<div class="container">
	<h1 class="muted">CeusMedia Component Demo</h1>
	<h2>Mail</h2>
	<h3>Live Demo</h3>
	<div class="row-fluid">
		<div class="span3">
			<form action="./" method="post">
				<label>Sender Name</label>
				<input type="text" class="span12" readonly="readonly" value="'.htmlentities( $sending->senderName, ENT_QUOTES, 'UTF-8' ).'"/>
				<label>Sender Address</label>
				<input type="text" class="span12" readonly="readonly" value="'.htmlentities( $sending->senderAddress, ENT_QUOTES, 'UTF-8' ).'"/>
				<label>Receiver Name</label>
				<input type="text" class="span12" readonly="readonly" value="'.htmlentities( $sending->receiverName, ENT_QUOTES, 'UTF-8' ).'"/>
				<label for="input_receiverAddress">Receiver Address</label>
				<input type="text" name="receiverAddress" class="span12" value="'.htmlentities( $request->get( 'receiverAddress', '' ), ENT_QUOTES, 'UTF-8' ).'"/>
				<button type="submit" name="send" class="btn btn-primary btn-large">send Mail</button>
			</form>
		</div>
		<div class="span9">
			<pre style="height: 300px; overflow-y: auto">'.$result.'</pre>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12">
			<h3>Code</h3>
			Create a mail, setup a transport and send mail via transport:
			<pre>
//  create mail
$mail = new \CeusMedia\Mail\Message();
$mail->setSender("john@example.com", "John Doe");
$mail->addRecipient("mike@example.com", "Mike Foo");
$mail->setSubject("This is just a test");
$mail->addPart(new \CeusMedia\Mail\Message\Part\Text("Test Message..."));

//  setup SMTP transport and send mail
$transport = new \CeusMedia\Mail\Transport\SMTP("example.com", 587);
$transport->setUsername("john@example.com");
$transport->setPassword("my_password");
$transport->setSecure(true);
$transport->send($mail);
			</pre>
			Or chained:
			<pre>
\CeusMedia\Mail\Transport\SMTP::getInstance("example.com", 587)
	->setUsername("john@example.com")
	->setPassword("my_password")
	->setSecure(true)
	->send(\CeusMedia\Mail\Message::getInstance()
		->setSender("john@example.com", "John Doe")
		->addRecipient("mike@example.com", "Mike Foo")
		->setSubject("This is just a test")
		->addPart(new \CeusMedia\Mail\Message\Part\Text("Test Message..."));
	);
			</pre>
		</div>
	</div>
</div>
';

$page	= new \CeusMedia\Common\UI\HTML\PageFrame();
$page->addBody( $body );
$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
print $page->build();

/*  --------------------------------------------------------------------  */

function sendMail( $config ) {
	$configSend	= (object) $config->getAll( 'sending_' );
	$configSmtp	= (object) $config->getAll( 'SMTP_' );
	$body		= new \CeusMedia\Mail\Message\Part\HTML( sprintf( $configSend->body, time() ) );
	$mail       = new \CeusMedia\Mail\Message();
	$mail->setSender( $configSend->senderAddress, $configSend->senderName );
	$mail->addRecipient( $configSend->receiverAddress, $configSend->receiverName );
	$mail->setSubject( sprintf( $configSend->subject, uniqid() ) );
	$mail->addPart( $body );

	$transport  = new \CeusMedia\Mail\Transport\SMTP(
		$configSmtp->host,
		$configSmtp->port,
		$configSmtp->username,
		$configSmtp->password
	);
	$transport->setVerbose( TRUE );
	$transport->setSecure( $configSmtp->port != 25 );
	$transport->send( $mail );
}
