<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Cache\Adapter\Folder as FolderCache;
use CeusMedia\Cache\Encoder\Serial as CacheEncoder;
use CeusMedia\Cache\SimpleCacheFactory as CacheFactory;
use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\Net\HTTP\Request\Receiver as HttpRequestReceiver;
use CeusMedia\Common\Net\HTTP\Response as HttpResponse;
use CeusMedia\Common\UI\HTML\Exception\Page as ExceptionPage;
use CeusMedia\Common\UI\HTML\PageFrame as Page;
use CeusMedia\Common\UI\HTML\Tag as Tag;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;
use CeusMedia\Mail\Message;


class DemoMailboxApp
{
	/** @var object{host: ?string, port: ?int, address: ?string, username: ?string, password: ?string} $config */
	protected object $config;
	protected FolderCache $cache;
	protected HttpRequestReceiver $request;
	protected HttpResponse $response;
	protected Mailbox $mailbox;

	public static string $urlCssLibrary		= 'https://cdn.ceusmedia.de/css/bootstrap.min.css';
	public static string $urlJsLibrary		= 'https://cdn.ceusmedia.de/js/jquery/1.10.2.min.js';

	/**
	 * @param Dictionary $config
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	public function __construct( Dictionary $config )
	{
		set_error_handler( [$this, 'handleError'] );

		try{
			$this->config	= (object) $config->getAll();
			$this->request	= new HttpRequestReceiver;
			$this->response	= new HttpResponse;
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			$this->cache	= CacheFactory::createStorage( 'Folder', 'cache/', NULL, 3600 );
			$this->cache->setEncoder( CacheEncoder::class );
			$this->openMailbox();
			$this->dispatch();
		}
		catch( Exception|Error $e ){
			ExceptionPage::display( $e );
		}
	}

	/**
	 * @param string $mailId
	 * @return Message
	 * @throws ReflectionException
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function getMailAsMessage( string $mailId ): Message
	{
		$cacheKey	= 'mail.'.$mailId;
		if( $this->cache->has( $cacheKey ) ){
			/** @var string $data */
			$data		= $this->cache->get( $cacheKey );
			/** @var Message $message */
			$message	= $data;
		}
		else{
			$message	= $this->mailbox->getMailAsMessage( (int) $mailId );
			$this->cache->set( $cacheKey, $message );
		}
		return $message;
	}

	/**
	 * @return string|void
	 * @throws ReflectionException
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function dispatch()
	{
		$action	= $this->request->get( 'action' );
		$mailId	= trim( $this->request->get( 'mailId' ) ?? '' );
		switch( $action ){
			case 'remove':
				if( '' === $mailId )
					return Tag::create( 'div', 'Keine Mail-ID übergeben.', array( 'class' => 'alert alert-error' ) );
				$this->mailbox->removeMail( (int) $mailId, TRUE );
				$this->cache->delete( 'mail.'.$mailId );
				$this->cache->delete( 'mail.'.$mailId.'.body' );
				$this->cache->delete( 'mail.'.$mailId.'.body.pure' );
				$this->request->remove( 'action' );
				$this->dispatch();
				break;
			case 'viewTextContent':
				if( '' === $mailId )
					return Tag::create( 'div', 'Keine Mail-ID übergeben.', array( 'class' => 'alert alert-error' ) );
				$message	= $this->getMailAsMessage( $mailId );
				$content	= Tag::create( 'xmp', $message->getText()->getContent() );
				print( $content );
				exit;
			case 'viewHtmlContent':
				if( '' === $mailId )
					return Tag::create( 'div', 'Keine Mail-ID übergeben.', array( 'class' => 'alert alert-error' ) );
				$message	= $this->getMailAsMessage( $mailId );
				$pure		= (bool) $this->request->get( 'pure', FALSE );
				$cacheKey	= 'mail.'.$mailId.'.body'.( $pure ? '.pure' : '' );
				if( $this->cache->has( $cacheKey ) )
					$content	= $this->cache->get( $cacheKey );
				else{
					$content	= $message->getHTML()->getContent();
					if( $pure )
						$content	= $this->purifyHtmlContent( $content ?? '' );
					else{
						foreach( $message->getInlineImages() as $image ){
							$content	= preg_replace(
								'/'.preg_quote( 'cid:'.substr( $image->getId(), 1, -1 ), '/' ).'/i',
								'data:image/jpg;base64,'.base64_encode( $image->getContent() ?? '' ),
								$content ?? ''
							);
						}
					}
				}
				$this->cache->set( $cacheKey, $content );
				print( $content );
				exit;
			default:
				$this->respondWithPage();
				exit;
		}
	}

	/**
	 * @param int $number
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @param array|null $context
	 * @return bool
	 * @throws ErrorException
	 */
	protected function handleError( int $number, string $message, string $file, int $line, ?array $context = NULL ): bool
	{
		if( 0 === error_reporting() )											// error was suppressed with the @-operator
			return FALSE;
		throw new ErrorException( $message, 0, $number, $file, $line );
	}

	/**
	 * @return void
	 * @throws ReflectionException
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function respondWithPage(): void
	{
		$page		= new Page();
		$page->addStylesheet( static::$urlCssLibrary  );
		$page->addStylesheet( 'style.css' );
		$page->addJavaScript( static::$urlJsLibrary );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/fonts/FontAwesome/4.7.0/css/font-awesome.min.css' );
		$page->addJavaScript( 'script.js' );
		$page->addBody( $this->renderIndex() );
		$this->response->setBody( $page->build() );
		$this->response->send();
//		ResponseSender::sendResponse( $this->response );
	}

	/**
	 * @return string
	 * @throws ReflectionException
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
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
					'style'		=> 'border: 0px; width: 69%; height: 100%; overflow: auto; position: fixed',
				) ),
			), array( 'id' => 'mail-ui-layout-preview' ) ),
		), array( 'id' => 'mail-ui-layout' ) );
		return $content;
	}

	/**
	 * @param array $mailIndex
	 * @return string
	 * @throws ReflectionException
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function renderIndexListAsTable( array $mailIndex ): string
	{
		$contacts	= [
			'kriss@ceusmedia.de'	=> [
				'icon'	=> 'https://office.ceusmedia.de/contents/avatars/2___348F47B2-2F18-4FDA-AE1D-017BBE0DB7AC.jpg',
			]
		];
		$icons		= [
			'ceusmedia.de'	=> 'https://%s/images/favicon.png',
		];
		$rows		= [];
		foreach( $mailIndex as $item ){
			$message		= $this->getMailAsMessage( $item );
			$senderName		= htmlentities( $message->getSender()?->getName( FALSE ) ?? '', ENT_QUOTES, 'UTF-8' );
			$senderAddress	= htmlentities( $message->getSender()?->getAddress() ?? '', ENT_QUOTES, 'UTF-8' );
			$senderDomain	= $message->getSender()?->getDomain() ?? '';
			$sender			= $senderAddress;
			if( '' !== $senderName ){
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
		return Tag::create( 'table', array( $rows ), array( 'class' => 'table' ) );
	}

	/**
	 * @param array $mailIndex
	 * @return string
	 * @throws ReflectionException
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function renderIndexListAsFlexList( array $mailIndex ): string
	{
		$rows		= array();
		foreach( $mailIndex as $item ){
			$message		= $this->getMailAsMessage( $item );

			$senderName		= htmlentities( $message->getSender()?->getName( FALSE ) ?? '', ENT_QUOTES, 'UTF-8' );
			$senderAddress	= htmlentities( $message->getSender()?->getAddress() ?? '', ENT_QUOTES, 'UTF-8' );
			$sender			= $senderAddress;
			if( '' !== trim( $senderName ) )
				$sender	= Tag::create( 'abbr', $senderName, array( 'title' => $senderAddress ) );

			$buttonRemove	= Tag::create( 'a', 'remove', array( 'href' => './?mailId='.$item.'&action=remove', 'class' => 'btn btn-mini btn-inverse' ) );
			$buttons	= Tag::create( 'div', array( $buttonRemove ), array( 'class' => 'btn-group' ) );
			$subject	= Tag::create( 'div', $message->getSubject() );
			$rows[]		= Tag::create( 'div', [
				Tag::create( 'div', '<i class="fa fa-circle-thin fa-3x fa-bordered"></i>', array( 'class' => 'mail-list-item-icon' ) ),
				Tag::create( 'div', [
					Tag::create( 'div', $subject, ['class' => 'mail-list-item-subject'] ),
					Tag::create( 'div', $sender, ['class' => 'mail-list-item-sender'] ),
				], ['class' => 'mail-list-item-main'] ),
				Tag::create( 'div', $buttons, array( 'class' => 'mail-list-item-buttons' ) ),
			], ['class' => 'mail-list-item mail-list-preview-trigger'], [
				'id'	=> $item,
				'html'	=> $message->hasHTML(),
				'text'	=> $message->hasText()
			] );
		}
		return Tag::create( 'div', $rows );
	}

	protected function openMailbox( bool $secure = TRUE ): void
	{
		$host		= $this->config->host ?? '';
		$address	= $this->config->address ?? '';
		$username	= $this->config->username ?? '';
		$password	= $this->config->password ?? '';
		if( '' === $address )
			throw new RuntimeException( 'Error: No mailbox address defined.' );
		if( '' === $username )
			throw new RuntimeException( 'Error: No mailbox user name defined.' );
		if( '' === $password )
			throw new RuntimeException( 'Error: No mailbox user password defined.' );

		$connection		= new Connection( $host, $username, $password );
		$connection->setSecure( $secure, $secure );
		$this->mailbox	= new Mailbox( $connection );
	}

	protected function purifyHtmlContent( string $content ): string
	{
		$purifierConfig	= HTMLPurifier_Config::createDefault();
		$purifierConfig->set( 'Cache.SerializerPath', 'cache/' );
		$purifierConfig->set( 'Cache.SerializerPermissions', NULL );

		$purifier		= new HTMLPurifier( $purifierConfig );
		$contentPure	= $purifier->purify( $content );
		$bodyContainer	= Tag::create( 'div', $contentPure, ['class' => 'container-fluid'] );

		$page	= new Page();
		$page->addStylesheet( static::$urlCssLibrary );
		$page->addBody( $bodyContainer );
		return $page->build();
	}
}
