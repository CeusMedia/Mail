<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;

/** @var Dictionary $config */

$defaults = ['folderSent' => 'INBOX.Sent'];


/** @var object{address: ?string, username: ?string, password: ?string, host: ?string, folderSent: string } $configMailbox */
$configMailbox	= (object) array_merge( $defaults, (array) $config->getAll( 'mailbox_' ) );

$connection		= connectConnection( $configMailbox );

$upload			= new Mailbox\Upload( $connection, $configMailbox->folderSent );
//$upload->setBasicFlags( Mailbox\Upload::FLAG_SEEN );

try{
	$message	= generateMessage( $config );
	$result		= $upload->storeMessage( $message );
	print_m( $result );
} catch ( Exception $e ) {
	print_m( $e->getMessage() );
}
$connection->disconnect();
exit;

/**
 * @param object{address: ?string, username: ?string, password: ?string, host: ?string } $config
 * @return Connection
 */
function connectConnection( object $config ): Connection
{
	if( NULL === $config->host || '' == trim( $config->host ) )
		die( 'Error: No mailbox host defined.' );
	if( NULL === $config->address || '' == trim( $config->address ) )
		die( 'Error: No mailbox address defined.' );
	if( NULL === $config->username || '' == trim( $config->username ) )
		die( 'Error: No mailbox user name defined.' );
	if( NULL === $config->password || '' == trim( $config->password ) )
		die( 'Error: No mailbox user password defined.' );
	return new Connection(
		$config->host,
		$config->username,
		$config->password,
		TRUE,
		TRUE
	);
}

/**
 *	@param		Dictionary		$config
 *	@return		Message
 */
function generateMessage( Dictionary $config ): Message
{
	/** @var object{senderAddress: ?string, senderName: ?string, receiverAddress: ?string, receiverName: ?string, folderSent: string } $configSending */
	$configSending	= (object) $config->getAll( 'sending_' );

	$id			= strtoupper( substr( (string) preg_replace( '/(\d-)/', '', md5( (string) microtime( TRUE ) ) ), 3, 6 ) );
	$sender		= new Address( $configSending->senderAddress );
	$receiver	= new Address( $configSending->receiverAddress );
	if( NULL !== $configSending->senderName )
		$sender->setName( $configSending->senderName );
	if( NULL !== $configSending->receiverName )
		$receiver->setName( $configSending->receiverName );

	return Message::getInstance()
		->setSubject( 'Test Message #'.$id )
		->setSender( $sender )
		->addRecipient( $receiver )
		->addText( 'ID: '.$id.PHP_EOL.'Timestamp: '.time().PHP_EOL.'Date: '.date( 'Y-m-d H:i:s' ).PHP_EOL )
	;

}