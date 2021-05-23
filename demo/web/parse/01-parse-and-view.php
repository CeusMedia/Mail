<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

$fileName	= "../../mails/01-simple-7bit";
$fileName	= "../../mails/02-simple-umlauts";
$fileName	= "../../mails/03-simple-printable";
$fileName	= "../../mails/04-simple-base64";
$fileName	= "../../mails/05-simple-attachment";
$fileName	= "../../mails/07-complex-fail-forward";
$fileName	= "../../mails/00-header-partly";
$fileName	= "../../mails/08-complex-attachment";

//  --------------------------------  //

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Message\Renderer;
use UI_HTML_PageFrame as Page;
use UI_HTML_Exception_Page as ExceptionPage;
use UI_HTML_Tag as Tag;
new UI_DevOutput;

try{
	$demo	= new ParseAndView();
	$demo->setFilePath( $fileName )->run();
}
catch( Exception $e ){
	ExceptionPage::display( $e );
}

//  --------------------------------  //

class ParseAndView
{
	protected $filePath;

	public function __construct( string $filePath = NULL )
	{
		if( !getEnv( 'HTTP_HOST' ) )
			die( "This demo is for browser, only" );
		if( !is_null( $filePath ) )
			$this->setFilePath( $filePath );
	}

	public function run()
	{
		if( !$this->filePath )
			die( 'No file selected' );

		$rawMail	= file_get_contents( $this->filePath );
		$html		= $this->renderPage( $rawMail );
 		print( $html);
	}

	public function setFilePath( string $filePath ): self
	{
		if( !file_exists( $filePath ) )
			throw new DomainException( 'Given demo mail file is not existing' );
		$this->filePath	= $filePath;
		return $this;
	}

	//  --  PROTECTED  --  //

	protected function addListItem( & $list, $key, $value )
	{
		$value	= htmlentities( $value, ENT_QUOTES, 'UTF-8' );;
		$list[]	= UI_HTML_Tag::create( 'li', $key.': '.$value );
	}

	protected function renderListMain( Message $object ): string
	{
		$listMain	= array();
		$this->addListItem( $listMain, 'Subject', $object->getSubject() );
		$this->addListItem( $listMain, 'Date', current( $object->getHeaders()->getFieldsByName( 'Date' ) )->getValue() );
		$this->addListItem( $listMain, 'From', $object->getSender()->get() );
		foreach( $object->getRecipients() as $type => $recipients ){
			foreach( $recipients as $recipient ){
				$this->addListItem( $listMain, ucfirst( $type ), $recipient->get() );
			}
		}
		$headerReturnPath	= $object->getHeaders()->getFieldsByName( 'Return-Path' );
		$headerDeliveredTo	= $object->getHeaders()->getFieldsByName( 'Delivered-To' );
		if( $headerDeliveredTo )
			$this->addListItem( $listMain, 'Delivered-To', $headerDeliveredTo[0]->getValue() );
		if( $headerReturnPath )
			$this->addListItem( $listMain, 'Return', $headerReturnPath[0]->getValue() );
		$this->addListItem( $listMain, 'ID', current( $object->getHeaders()->getFieldsByName( 'Message-ID' ) )->getValue() );
		return UI_HTML_Tag::create( 'ul', $listMain );
	}

	protected function renderListParts( Message $object ): string
	{
		$parts		= $object->getParts( TRUE );
		$listParts		= array();
		foreach( $parts as $nr => $part ){
			$list2		= array();
			$listParts[]	= UI_HTML_Tag::create( 'h4', 'Part #'.( $nr + 1 ) );
			$this->addListItem( $list2, 'Type', Alg_Object_Constant::staticGetKeyByValue( '\CeusMedia\Mail\Message\Part', $part->getType(), 'TYPE_' ) );
			if( $part->getCharset() )
				$this->addListItem( $list2, 'Charset', $part->getCharset() );
			if( $part->getEncoding() )
				$this->addListItem( $list2, 'Encoding', $part->getEncoding() );
			if( $part->getFormat() )
				$this->addListItem( $list2, 'Format', $part->getFormat() );
			$this->addListItem( $list2, 'Class', get_class( $part ) );
			if( $part->isInlineImage() )
				$this->addListItem( $list2, 'Content-ID', $part->getId() );
			if( $part->isAttachment() ){
				if( $part->getFileName() )
					$this->addListItem( $list2, 'Filename', $part->getFileName() );
				if( $part->getFileSize() )
					$this->addListItem( $list2, 'Filesize', $part->getFileSize() );
			}
			$listParts[]	= UI_HTML_Tag::create( 'ul', $list2 );
			if( $part->isText() ){
				$listParts[]	= UI_HTML_Tag::create( 'h5', 'Content' );
				$listParts[]	= UI_HTML_Tag::create( 'xmp', $part->getContent() );
			}
			else if( $part->isHTML() ){
				$listParts[]	= UI_HTML_Tag::create( 'h5', 'Content' );
				$listParts[]	= UI_HTML_Tag::create( 'xmp', $part->getContent() );
			}
		}
		return join( $listParts );
	}

	protected function renderListMore( Message $object ): string
	{
		$listMore		= array();
		foreach( $object->getHeaders()->getFields() as $header ){
			if( in_array( $header->getName(), array( 'Date', 'From', 'To', 'Subject', 'Message-ID', 'Return-Path', 'Delivered-To' ) ) )
				continue;
			if( preg_match( '/^X-/', $header->getName() ) )
				continue;
			if( !strlen( trim( $header->getValue() ) ) )
				continue;
			$this->addListItem( $listMore, $header->getName(), $header->getValue() );
		}
		return UI_HTML_Tag::create( 'ul', $listMore );
	}

	protected function renderListExtended( Message $object ): string
	{
		$listExtended	= array();
		foreach( $object->getHeaders()->getFields() as $header ){
			if( !preg_match( '/^X-/', $header->getName() ) )
				continue;
			if( !strlen( trim( $header->getValue() ) ) )
				continue;
			$this->addListItem( $listExtended, $header->getName(), $header->getValue() );
		}
		return UI_HTML_Tag::create( 'ul', $listExtended );
	}

	protected function renderPage( string $rawMail ): string
	{
		$object			= Parser::getInstance()->parse( $rawMail );

		$listMain		= $this->renderListMain( $object );
		$listParts		= $this->renderListParts( $object );
		$listMore		= $this->renderListMore( $object );
		$listExtended	= $this->renderListExtended( $object );

		$page	= new Page();
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addBody( Tag::create( 'div', array(
			Tag::create( 'div', array(
				Tag::create( 'h2', 'Source' ),
				Tag::create( 'pre', htmlentities( $rawMail, ENT_QUOTES, 'UTF-8' ) ),
			), array( 'class' => 'span5' ) ),
			Tag::create( 'div', array(
				Tag::create( 'h2', 'Parsed' ),
				Tag::create( 'div', array(
					Tag::create( 'h3', 'Main Headers' ),
					Tag::create( 'div', $listMain ),
					Tag::create( 'h3', 'Parts' ),
					Tag::create( 'div', $listParts ),
					Tag::create( 'h3', 'More Headers' ),
					Tag::create( 'div', $listMore ),
					Tag::create( 'h3', 'Extended Headers' ),
					Tag::create( 'div', $listExtended ),
				) ),
			), array( 'class' => 'span7' ) ),
		), array( 'class' => 'row-fluid' ) ) );
		return $page->build();
	}
}
