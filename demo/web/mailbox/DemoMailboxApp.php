<?php
new UI_DevOutput;

use CeusMedia\Mail\Mailbox;
use CeusMedia\Cache\Factory as CacheFactory;
use UI_HTML_PageFrame as Page;
use UI_HTML_Tag as Tag;


class DemoMailboxApp{

	protected $mailbox;
	protected $request;
	protected $response;

	public static $urlCssLibrary	= 'https://cdn.ceusmedia.de/css/bootstrap.min.css';
	public static $urlJsLibrary		= 'https://cdn.ceusmedia.de/js/jquery/1.10.2.min.js';

	public function __construct( ADT_List_Dictionary $config )
	{
		$this->config	= (object) $config->getAll( 'mailbox_' );
		$this->request	= new Net_HTTP_Request_Receiver;
		$this->response	= new Net_HTTP_Response;
		$this->cache	= CacheFactory::createStorage('Folder', 'cache/', NULL, 3600);
		try{
			$this->openMailbox();
			$this->dispatch();
		}
		catch( \Exception $e ){
			UI_HTML_Exception_Page::display( $e );
		}
	}

	protected function getMailAsMessage( string $mailId )
	{
		$cacheKey	= 'mail.'.$mailId;
		if( $this->cache->has( $cacheKey ) ){
			$message	= unserialize( $this->cache->get( $cacheKey ) );
		}
		else{
			$message	= $this->mailbox->getMailAsMessage( (int) $mailId );
			$this->cache->set($cacheKey, serialize( $message ) );
		}
		return $message;
	}

	protected function dispatch()
	{
		$action	= $this->request->get( 'action' );
		$mailId	= $this->request->get( 'mailId' );
		switch( $action ){
			case 'remove':
				if( !$mailId )
					return Tag::create( 'div', 'Keine Mail-ID übergeben.', array( 'class' => 'alert alert-error' ) );
				$this->mailbox->removeMail( $mailId, TRUE );
				$this->cache->remove( 'mail.'.$mailId );
				$this->cache->remove( 'mail.'.$mailId.'.body' );
				$this->cache->remove( 'mail.'.$mailId.'.body.pure' );
				$this->request->remove( 'action' );
				return $this->dispatch();
			case 'viewTextContent':
				if( !$mailId )
					return Tag::create( 'div', 'Keine Mail-ID übergeben.', array( 'class' => 'alert alert-error' ) );
				$message	= $this->getMailAsMessage( $mailId );
				$content	= Tag::create( 'xmp', $message->getText()->getContent() );
				print( $content );
				exit;
			case 'viewHtmlContent':
				if( !$mailId )
					return Tag::create( 'div', 'Keine Mail-ID übergeben.', array( 'class' => 'alert alert-error' ) );
				$message	= $this->getMailAsMessage( $mailId );
				$pure		= !TRUE;
				$cacheKey	= 'mail.'.$mailId.'.body'.( $pure ? '.pure' : '' );
				if( $this->cache->has( $cacheKey ) )
					$content	= $this->cache->get( $cacheKey );
				else{
					if( $pure ){
						$content	= $message->getHtml()->getContent();
						$content	= $this->purifyHtmlContent( $content );
					}
					else{
						$content	= $message->getHtml()->getContent();
						foreach( $message->getInlineImages() as $image ){
							$content	= preg_replace(
								'/'.preg_quote( 'cid:'.substr( $image->getId(), 1, -1 ), '/' ).'/i',
								'data:image/jpg;base64,'.base64_encode( $image->getContent() ),
								$content
							);
						}
					}
				}
				$this->cache->set( $cacheKey, $content );
				print( $content );
				exit;
			default:
				$content	= $this->renderIndex();
		}
		$page	= new Page();
		$page->addStylesheet( static::$urlCssLibrary  );
		$page->addStylesheet( 'style.css' );
		$page->addJavaScript( static::$urlJsLibrary );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/fonts/FontAwesome/4.7.0/css/font-awesome.min.css' );
		$page->addJavaScript( 'script.js' );
		$page->addBody( $content );
		$this->response->setBody( $page->build() );
		Net_HTTP_Response_Sender::sendResponse( $this->response );
	}

	protected function renderIndex(): string
	{
		$this->openMailbox();
		$mailIndex	= array_slice( $this->mailbox->index(), 0, 20 );
		if( 1 )
			$list		= $this->renderIndexListAsTable( $mailIndex );
		else
			$list		= $this->renderIndexListAsFlexList( $mailIndex );

		$content	= Tag::create( 'div', array(
			Tag::create( 'div', array(
				$list,
			), array( 'id' => 'mail-ui-layout-list' ) ),
			Tag::create( 'div', array(
				Tag::create( 'iframe', '', array(
					'id'		=> 'mail-ui-preview-iframe',
					'style'		=> 'border: 0px; width: 100%; height: 100%; overflow: auto; position: fixed',
				) ),
			), array( 'id' => 'mail-ui-layout-preview' ) ),
		), array( 'id' => 'mail-ui-layout' ) );
		return (string) $content;
	}

	protected function renderIndexListAsTable( array $mailIndex ): string
	{
		$contacts	= array(
			'kriss@ceusmedia.de'	=> array(
				'icon'	=> 'https://office.ceusmedia.de/contents/avatars/2___348F47B2-2F18-4FDA-AE1D-017BBE0DB7AC.jpg',
			)
		);
		$icons		= array(
			'ceusmedia.de'	=> 'https://%s/images/favicon.png',
		);
		$rows		= array();
		foreach( $mailIndex as $item ){
			$message		= $this->getMailAsMessage( $item );
			$senderName		= htmlentities( $message->getSender()->getName( FALSE ), ENT_QUOTES, 'UTF-8' );
			$senderAddress	= htmlentities( $message->getSender()->getAddress(), ENT_QUOTES, 'UTF-8' );
			$senderDomain	= $message->getSender()->getDomain();
			$sender			= $senderAddress;
			if( $senderName ){
				$sender	= $senderName.'&nbsp;'.Tag::create( 'span', '&lt;'.$senderAddress.'&gt;', array( 'class' => 'muted' ) );
			}
			$buttonRemove	= Tag::create( 'a', 'remove', array( 'href' => './?mailId='.$item.'&action=remove', 'class' => 'btn btn-mini btn-inverse' ) );
			$buttons	= Tag::create( 'div', array( $buttonRemove ), array( 'class' => 'btn-group' ) );
			$icon		= Tag::create( 'i', '', array( 'class' => 'fa fa-2x fa-border fa-envelope' ) );

			if( array_key_exists( $senderAddress, $contacts ) )
				$icon		= Tag::create( 'img', NULL, array( 'src' => $contacts[$senderAddress]['icon'], 'style' => 'width: 44px' ) );
			else if( array_key_exists( $senderDomain, $icons ) )
				$icon		= Tag::create( 'img', NULL, array( 'src' => sprintf( $icons[$senderDomain], $senderDomain ), 'style' => 'width: 44px' ) );
//			else
//				$icon		= Tag::create( 'img', NULL, array( 'src' => 'https://'.$senderDomain.'/favicon.ico', 'style' => 'width: 32px' ) );


			$rows[]	= Tag::create( 'tr', array(
				Tag::create( 'td', $icon, array( 'class' => 'mail-list-item-cell-icon' ) ),
				Tag::create( 'td', array(
					Tag::create( 'div', '<div>'.$message->getSubject().'</div>', array( 'class' => 'mail-list-item-cell-subject' ) ),
					Tag::create( 'div', $sender, array( 'class' => 'mail-list-item-cell-sender' ) ),
				), array( 'class' => 'mail-list-item-cell-main mail-list-preview-trigger' ), array( 'id' => $item, 'html' => $message->hasHTML(), 'text' => $message->hasText() ) ),
				Tag::create( 'td', $buttons, array( 'class' => 'mail-list-item-cell-buttons' ) ),
			), array( 'class' => 'mail-list-item-row' ) );
		}
		$table		= Tag::create( 'table', array( $rows ), array( 'class' => 'table' ) );
		return $table;
	}

	protected function renderIndexListAsFlexList( array $mailIndex ): string
	{
		$rows		= array();
		foreach( $mailIndex as $item ){
			$message		= $this->getMailAsMessage( $item );
			$senderName		= htmlentities( $message->getSender()->getName(), ENT_QUOTES, 'UTF-8' );
			$senderAddress	= htmlentities( $message->getSender()->getAddress(), ENT_QUOTES, 'UTF-8' );
			$sender			= $senderAddress;
			if( $senderName ){
				$sender	= Tag::create( 'abbr', $senderName, array( 'title' => $senderAddress ) );
			}
			$buttonRemove	= Tag::create( 'a', 'remove', array( 'href' => './?mailId='.$item.'&action=remove', 'class' => 'btn btn-mini btn-inverse' ) );
			$buttons	= Tag::create( 'div', array( $buttonRemove ), array( 'class' => 'btn-group' ) );
			$subject	= Tag::create( 'div', $message->getSubject() );
			$rows[]		= Tag::create( 'div', array(
				Tag::create( 'div', '<i class="fa fa-circle-thin fa-3x fa-bordered"></i>', array( 'class' => 'mail-list-item-icon' ) ),
				Tag::create( 'div', array(
					Tag::create( 'div', $subject, array( 'class' => 'mail-list-item-subject' ) ),
					Tag::create( 'div', $sender, array( 'class' => 'mail-list-item-sender' ) ),
				), array( 'class' => 'mail-list-item-main' ) ),
				Tag::create( 'div', $buttons, array( 'class' => 'mail-list-item-buttons' ) ),
			), array( 'class' => 'mail-list-item mail-list-preview-trigger' ), array( 'id' => $item, 'html' => $message->hasHTML(), 'text' => $message->hasText() ) );
		}

		$list	= Tag::create( 'div', $rows );
		return $list;
	}

	protected function openMailbox( bool $force = FALSE, bool $secure = TRUE )
	{
		if( $this->mailbox && !$force ){
			return;
		}
		if( !$this->config->address ){
			throw new \RuntimeException( 'Error: No mailbox address defined.' );
		}
		if( !$this->config->username ){
			throw new \RuntimeException( 'Error: No mailbox user name defined.' );
		}
		if( !$this->config->password ){
			throw new \RuntimeException( 'Error: No mailbox user password defined.' );
		}

		$this->mailbox	= new Mailbox( $this->config->host, $this->config->username, $this->config->password );
		$this->mailbox->setSecure( $secure, $secure );
		$this->mailbox->connect();
	}

	protected function purifyHtmlContent( string $content ): string
	{
		$purifierConfig	= HTMLPurifier_Config::createDefault();
		$purifierConfig->set( 'Cache.SerializerPath', 'cache/' );
		$purifierConfig->set( 'Cache.SerializerPermissions', NULL );
		$purifier	= new HTMLPurifier( $purifierConfig );
		$contentPure	= $purifier->purify( $content );
		$bodyContainer	= Tag::create( 'div', $contentPure, array(
			'class' => 'container-fluid'
		) );
		$page	= new Page();
		$page->addStylesheet( static::$urlCssLibrary );
		$page->addBody( $bodyContainer );
		return $page->build();
	}
}
