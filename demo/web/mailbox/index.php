<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Mailbox;
use UI_HTML_PageFrame as Page;
use UI_HTML_Tag as Tag;

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
		$mailId	= $this->request->get( 'mailId' );
		switch( $action ){
			case 'remove':
				$this->mailbox->removeMail( $mailId, TRUE );
				$this->request->remove( 'action' );
				return $this->dispatch();
				break;
			case 'viewHtmlContent':
			$message	= $this->mailbox->getMailAsMessage( (int) $mailId );
				$message	= $this->mailbox->getMailAsMessage( (int) $mailId );
				$content	= $message->getHtml()->getContent();
				foreach( $message->getInlineImages() as $image ){
					$content	= preg_replace(
						'/'.preg_quote( 'cid:'.substr( $image->getId(), 1, -1 ), '/' ).'/i',
						'data:image/jpg;base64,'.base64_encode( $image->getContent() ),
						$content
					);
				}
				print( $content );
				exit;
			default:
				$content	= $this->renderIndex();
		}
		$page	= new Page();
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addBody( $content );
		$this->response->setBody( $page->build() );
		Net_HTTP_Response_Sender::sendResponse( $this->response );
	}

	protected function renderIndex(){
		$this->openMailbox();
		$mailIndex	= array_slice( $this->mailbox->index(), 0, 2 );
		$rows		= array();
		foreach( $mailIndex as $item ){
			$message		= $this->mailbox->getMailAsMessage( $item );
			$senderName		= htmlentities( $message->getSender()->getName(), ENT_QUOTES, 'UTF-8' );
			$senderAddress	= htmlentities( $message->getSender()->getAddress(), ENT_QUOTES, 'UTF-8' );
			$sender			= $senderAddress;
			if( $senderName )
				$sender	= Tag::create( 'abbr', $senderName, array( 'title' => $senderAddress ) );
			$buttonRemove	= Tag::create( 'a', 'remove', array( 'href' => './?mailId='.$item.'&action=remove', 'class' => 'btn btn-mini btn-inverse' ) );
			$buttons	= Tag::create( 'div', array( $buttonRemove ), array( 'class' => 'btn-group' ) );
			$rows[]	= Tag::create( 'tr', array(
				Tag::create( 'td', $message->getSubject() ),
				Tag::create( 'td', $sender ),
				Tag::create( 'td', $buttons ),
			), array( 'class' => '' ) );

			if( $message->hasHTML() ){
				$iframe		= Tag::create( 'iframe', '', array(
					'style'		=> 'border: 0px; width: 100%; height: 400px; overflow: auto;',
					'src'		=> './?mailId='.$item.'&action=viewHtmlContent',
				) );
				$cell		= Tag::create( 'td', $iframe, array( 'colspan' => 3, 'style' => 'padding: 0' ) );
				$rows[]		= Tag::create( 'tr', $cell, array( 'class' => '' ) );
			}
			else if( $message->hasText() ){
				$xmp		= Tag::create( 'xmp', $message->getText()->getContent() );
				$cell		= Tag::create( 'td', $xmp, array( 'colspan' => 3, 'style' => 'padding: 0' ) );
				$rows[]		= Tag::create( 'tr', $cell, array( 'class' => '' ) );
			}
		}
		$table	= Tag::create( 'table', array( $rows ), array( 'class' => 'table' ) );
		return $table;
	}

	protected function openMailbox( $force = FALSE, $secure = TRUE ){
		if( $this->mailbox && !$force )
			return;
		if( !$this->config->address )
			die( 'Error: No mailbox address defined.' );
		if( !$this->config->username )
			die( 'Error: No mailbox user name defined.' );
		if( !$this->config->password )
			die( 'Error: No mailbox user password defined.' );
		$this->mailbox	= new Mailbox( $this->config->host, $this->config->username, $this->config->password );
		$this->mailbox->setSecure( $secure, $secure );
		$this->mailbox->connect();
	}
}

$app	= new DemoMailboxApp( $config );
