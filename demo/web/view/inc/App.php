<?php

namespace CeusMedia\MailDemo\Web\View;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser;
use CeusMedia\Common\UI\HTML\PageFrame as Page;

new CeusMedia\Common\UI\DevOutput;

class App
{
	protected ?string $filePath;
	protected array $files;

	public function __construct()
	{
		if( !getEnv( 'HTTP_HOST' ) )
			die( "This demo is for browser, only" );
		$this->files	= $this->indexFiles();
		if( 0 === count( $this->files ) )
			throw new RuntimeException( 'No mail files found' );
	}

	public function run()
	{
		$file		= $_REQUEST['file'] ?? current( $this->files );
		$message	= $this->loadMessageFromFilename( $file );

		$action		= strtolower( $_REQUEST['action'] ?? '' );
		$type		= strtolower( $_REQUEST['type'] ?? '' );
		$part		= $_REQUEST['part'] ?? '';
		switch( $action ){
			case 'view':
				switch( $type ){
					case 'html':
						$this->viewHtml( $file, $message );
						break;
					case 'attachment':
						$this->viewAttachment( $file, $message, $part );
						break;
					case 'image':
						$id		= $_REQUEST['id'] ?? '';
						$this->viewImage( $file, $message, $part, $id );
						break;
				}
				break;
			case 'download':
				switch( $type ){
					case 'attachment':
						$this->downloadAttachment( $file, $message, $part );
						break;
					case 'image':
						$id		= $_REQUEST['id'] ?? '';
						$this->downloadImage( $file, $message, $part, $id );
						break;
				}
				break;
			default:
				$html	= $this->renderPage( $file, $message );
				print( $html);
		}
	}

	public function setFilePath( string $filePath ): self
	{
		if( !file_exists( $filePath ) )
			throw new DomainException( 'Given demo mail file is not existing' );
		$this->filePath	= $filePath;
		return $this;
	}

	public function viewAttachment( string $file, Message $message, $part = 0 )
	{
		$attachments	= $message->getAttachments();
		$attachment		= $attachments[$part];

		header( 'Content-Type: '.$attachment->getMimeType() );
		print( $attachment->getContent() );
		exit;
	}

	public function viewImage( string $file, Message $message, $part, $id )
	{
		$attachments	= $message->getInlineImages();
		$image			= $attachments[$part];
		header( 'Content-Type: '.$image->getMimeType() );
		print( $image->getContent() );
		exit;
	}

	public function downloadAttachment( string $file, Message $message, $part = 0 )
	{
		$attachments	= $message->getAttachments();
		$attachment		= $attachments[$part];
		Net_HTTP_Download::sendString( $attachment->getContent(), $attachment->getFileName() );
	}

	public function downloadImage( string $file, Message $message, $part = 0 )
	{
		$parts	= $message->getInlineImages();
		$image	= $parts[$part];
		Net_HTTP_Download::sendString( $image->getContent(), $image->getFileName() );
	}

	public function viewHtml( string $file, Message $message )
	{
		$part		= $message->getHTML();
		$html		= $part->getContent();
		foreach( $message->getInlineImages() as $part ){
			$image	= base64_encode( $part->getContent() );
			$html	= str_ireplace( 'cid:'.$part->getId(), 'data:image/png;base64,'.$image, $html );
		}
		print( $html );
		exit;
	}

	//  --  PROTECTED  --  //

	protected function indexFiles(): array
	{
		$files	= [];
		foreach( new DirectoryIterator( __DIR__.'/../../../mails' ) as $entry ){
			if( $entry->isDir() || $entry->isDot() )
				continue;
			$files[$entry->getPathname()]	= $entry->getFilename();
		}
		ksort( $files );
		return $files;
	}

	protected function loadMessageFromFilename( $fileName ): Message
	{
		$filePath	= array_search( $fileName, $this->files );
		$rawMail	= file_get_contents( $filePath );
		return Parser::getInstance()->parse( $rawMail );
	}

	protected function renderPage( string $file, Message $message ): string
	{
		$page		= new Page();
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/fonts/FontAwesome/4.7.0/css/font-awesome.min.css' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.10.2.min.js' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );

		$files	= $this->files;
		$page->addBody( require( __DIR__.'/index.phpt' ) );
		return $page->build();
	}
}
