<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\CLI;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part\Text;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\Mail\Transport\SMTP\Auth\Login;
use CeusMedia\Mail\Transport\SMTP\Auth\Plain;
use CeusMedia\Mail\Transport\SMTP\Auth\Xoauth2;

/** @var Dictionary $config */

/** @var object{host: ?string, port: ?int, username: ?string, password: ?string} $smtp */
$smtp		= (object) $config->getAll( 'SMTP_' );

/** @var object{senderAddress: ?string, senderName: ?string, receiverAddress: ?string, receiverName: ?string, subject: ?string} $sending */
$sending	= (object) $config->getAll( 'sending_' );

//  --  PLEASE CONFIGURE!  --  //

$verbose	= TRUE;

//  Select an auth strategy to demonstrate (uncomment one):
//  'login'   — AUTH LOGIN (default, also used automatically when username/password are set)
//  'plain'   — AUTH PLAIN (RFC 4616, single-step SASL authentication)
//  'xoauth2' — AUTH XOAUTH2 (OAuth 2.0, requires access token)
$authMethod	= 'login';

//  For XOAUTH2, provide the OAuth 2.0 access token:
//  $oauthToken	= 'ya29.a0AfH6SMB...';  //  Google OAuth 2.0 token example

// --  NO CHANGES NEEDED BELOW  --  //

CLI::out( 'Auth Strategy Demo' );
CLI::out( str_repeat( '=', 60 ) );

try {
	$mail	= new Message();
	$mail->setSender( $sending->senderAddress ?? '', $sending->senderName ?? '' );
	$mail->addRecipient( $sending->receiverAddress ?? '', $sending->receiverName ?? '' );
	$mail->setSubject( sprintf( $sending->subject ?? 'CeusMedia/Mail: Auth Demo #%s', uniqid() ) );
	$mail->addPart( new Text( 'This message was sent using the '.$authMethod.' auth strategy.', 'UTF-8', '7bit' ) );

	$transport	= new SMTP( $smtp->host ?? '', $smtp->port ?? 25 );
	$transport->setVerbose( $verbose );

	//  Configure the auth strategy based on selection
	switch ( $authMethod ) {
		case 'login':
			//  AUTH LOGIN: base64-encoded username and password in challenge-response
			//  This is the default when setUsername()/setPassword() are used
			$transport->setAuthStrategy( new Login( $smtp->username ?? '', $smtp->password ?? '' ) );
			CLI::out( 'Auth method: AUTH LOGIN (RFC 4616 challenge-response)' );
			break;

		case 'plain':
			//  AUTH PLAIN: single-step SASL authentication
			//  Sends: authz_id\0auth_id\0password (base64-encoded)
			//  Note: requires TLS, as credentials are sent in clear text (base64 only)
			$transport->setAuthStrategy( new Plain( $smtp->username ?? '', $smtp->password ?? '' ) );
			CLI::out( 'Auth method: AUTH PLAIN (RFC 4616, single-step SASL)' );
			break;

		case 'xoauth2':
			//  AUTH XOAUTH2: OAuth 2.0 authentication
			//  Used by Gmail, Microsoft 365, and other OAuth-enabled SMTP servers
			//  Supports SASL IR (Initial Response) for single-roundtrip authentication
			$oauthToken	= $oauthToken ?? '';
			if ( empty( $oauthToken ) ) {
				CLI::out( 'Error: XOAUTH2 requires an OAuth 2.0 access token. Configure $oauthToken above.' );
				exit;
			}
			$transport->setAuthStrategy( new Xoauth2( $smtp->username ?? '', $oauthToken ) );
			CLI::out( 'Auth method: AUTH XOAUTH2 (OAuth 2.0 with SASL IR)' );
			break;

		default:
			CLI::out( "Error: Unknown auth method '{$authMethod}'" );
			exit;
	}

	$transport->send( $mail );
	CLI::out( 'Mail sent successfully.' );

} catch ( Exception $e ) {
	CLI::out( 'Error: '.$e->getMessage() );
	CLI::out( 'Trace: '.PHP_EOL );
	CLI::out( ' - '.str_replace( "\n", "\n - ", $e->getTraceAsString() ) );
	exit;
}

if ( $verbose ) {
	CLI::out();
}
