<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\CLI;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part\Text;
use CeusMedia\Mail\Message\Signer\Dkim;
use CeusMedia\Mail\Transport\SMTP;

/** @var Dictionary $config */

/** @var object{host: ?string, port: ?int, username: ?string, password: ?string} $smtp */
$smtp		= (object) $config->getAll( 'SMTP_' );

/** @var object{senderAddress: ?string, senderName: ?string, receiverAddress: ?string, receiverName: ?string, subject: ?string} $sending */
$sending	= (object) $config->getAll( 'sending_' );

/** @var object{privateKey: ?string, domain: ?string, selector: ?string} $dkim */
$dkim		= (object) $config->getAll( 'DKIM_' );

//  --  PLEASE CONFIGURE!  --  //
//
//  DKIM (DomainKeys Identified Mail) signiert E-Mails mit einem privaten Schlüssel.
//  Der öffentliche Schlüssel wird als DNS-Text-Record veröffentlicht:
//    <selector>._domainkey.<domain>. IN TXT "v=DKIM1; k=rsa; p=<public_key>"
//
//  So erstellen Sie ein Schlüsselpaar:
//    openssl genrsa 2048 > dkim-private.pem
//    openssl rsa -in dkim-private.pem -pubout > dkim-public.pem
//
//  Konfigurieren Sie die DKIM_-Parameter in config.ini mit Ihrem Schlüssel,
//  Ihrer Domain und dem DNS-Selector.
//
//  @see https://datatracker.ietf.org/doc/html/rfc6376

$verbose	= TRUE;

// --  NO CHANGES NEEDED BELOW  --  //

CLI::out( 'DKIM Signing Demo' );
CLI::out( str_repeat( '=', 60 ) );

try {
	//  Check DKIM configuration
	if ( empty( $dkim->privateKey ) || empty( $dkim->domain ) || empty( $dkim->selector ) ) {
		CLI::out( 'DKIM is not configured. Add DKIM_privateKey, DKIM_domain, and DKIM_selector to config.ini.' );
		CLI::out( 'See the comments above for setup instructions.' );
		exit;
	}

	$mail	= new Message();
	$mail->setSender( $sending->senderAddress ?? '', $sending->senderName ?? '' );
	$mail->addRecipient( $sending->receiverAddress ?? '', $sending->receiverName ?? '' );
	$mail->setSubject( sprintf( $sending->subject ?? 'CeusMedia/Mail: DKIM Demo #%s', uniqid() ) );
	$mail->addPart( new Text( 'This message is DKIM-signed.', 'UTF-8', '7bit' ) );

	$transport	= new SMTP( $smtp->host ?? '', $smtp->port ?? 25 );
	$transport->setUsername( $smtp->username ?? '' );
	$transport->setPassword( $smtp->password ?? '' );
	$transport->setVerbose( $verbose );

	//  Create DKIM signer with private key
	//  The signer will:
	//    1. Canonicalize the message headers and body (relaxed mode)
	//    2. Compute the SHA-256 hash of the canonicalized content
	//    3. Sign the hash with the private key
	//    4. Add the DKIM-Signature header to the message
	$signer	= new Dkim(
		$dkim->privateKey,			//  Path to PEM-encoded private key file
		$dkim->domain,				//  DKIM domain (e.g., "example.com")
		$dkim->selector,			//  DNS selector (e.g., "default" or "2025")
		'relaxed',					//  Header canonicalization: "simple" or "relaxed"
		'relaxed'					//  Body canonicalization: "simple" or "relaxed"
	);

	//  Optionally customize which headers are included in the signature
	//  Default: from, date, subject (recommended minimum)
	//  $signer->setHeaders( ['from', 'date', 'subject', 'to', 'reply-to'] );

	$transport->setDkimSigner( $signer );
	CLI::out( 'DKIM signer configured:' );
	CLI::out( '  Domain:   '.$dkim->domain );
	CLI::out( '  Selector: '.$dkim->selector );
	CLI::out( '  Key file: '.$dkim->privateKey );

	$transport->send( $mail );
	CLI::out( 'Mail sent successfully with DKIM signature.' );

} catch ( Exception $e ) {
	CLI::out( 'Error: '.$e->getMessage() );
	CLI::out( 'Trace: '.PHP_EOL );
	CLI::out( ' - '.str_replace( "\n", "\n - ", $e->getTraceAsString() ) );
	exit;
}

if ( $verbose ) {
	CLI::out();
}
