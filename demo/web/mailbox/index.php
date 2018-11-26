<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Mailbox;

new UI_DevOutput;

class DemoMailboxApp{

	protected $mailbox;
	protected $request;
	protected $response;

	public function __construct( $config ){
		$this->config	= (object) $config->getAll( 'mailbox_' );
		$this->request	= new Net_HTTP_Request_Receiver;
		$this->response	= new Net_HTTP_Response;
		$this->openMailbox();
		$this->dispatch();
	}

	protected function dispatch(){
		$action	= $this->request->get( 'action' );
		switch( $action ){
			case 'aaa':
				break;
			default:
				$content	= $this->renderIndex();
		}
		$page	= new UI_HTML_PageFrame();
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addBody( $content );
		$this->response->setBody( $page->build() );
		Net_HTTP_Response_Sender::sendResponse( $this->response );
	}

	protected function renderIndex(){
		$this->openMailbox();
		$mailIndex	= array_slice( $this->mailbox->index(), 0, 1 );
		$rows		= array();
		foreach( $mailIndex as $item ){
			$message		= $this->mailbox->getMailAsMessage( $item );
			$senderName		= htmlentities( $message->getSender()->getName(), ENT_QUOTES, 'UTF-8' );
			$senderAddress	= htmlentities( $message->getSender()->getAddress(), ENT_QUOTES, 'UTF-8' );
			$sender			= $senderAddress;
			if( $senderName )
				$sender	= UI_HTML_Tag::create( 'abbr', $senderName, array( 'title' => $senderAddress ) );
			$rows[]	= UI_HTML_Tag::create( 'tr', array(
				UI_HTML_Tag::create( 'td', $message->getSubject() ),
				UI_HTML_Tag::create( 'td', $sender ),
			), array( 'class' => '' ) );
			$content	= $message->getText()->getContent();
			$rows[]	= UI_HTML_Tag::create( 'tr', array(
				UI_HTML_Tag::create( 'td', '<xmp>'.$content.'</xmp>', array( 'colspan' => 2 ) ),
			), array( '') );
		}
		$table	= UI_HTML_Tag::create( 'table', array( $rows ), array( 'class' => 'table' ) );
		return $table;
	}

	protected function openMailbox( $force = FALSE ){
		if( $this->mailbox && !$force )
			return;
		if( !$this->config->address )
			die( 'Error: No mailbox address defined.' );
		if( !$this->config->username )
			die( 'Error: No mailbox user name defined.' );
		if( !$this->config->password )
			die( 'Error: No mailbox user password defined.' );
		$this->mailbox	= new Mailbox( $this->config->host, $this->config->username, $this->config->password );
		$this->mailbox->setSecure( FALSE, FALSE );
		$this->mailbox->connect();
	}
}

$app	= new DemoMailboxApp( $config );
