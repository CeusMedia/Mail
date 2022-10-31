<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

$fileName	= array_keys( $files )[1];

//  --------------------------------  //

use CeusMedia\Common\Alg\Obj\Constant as ObjectConstant;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Common\UI\HTML\Exception\Page as ExceptionPage;
use CeusMedia\Common\UI\HTML\PageFrame as Page;
use CeusMedia\Common\UI\HTML\Tag as Tag;
new CeusMedia\Common\UI\DevOutput;

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
	protected string $filePath;

	public function __construct( string $filePath = NULL )
	{
		if( FALSE === getenv( 'HTTP_HOST' ) )
			die( "This demo is for browser, only" );
		if( !is_null( $filePath ) )
			$this->setFilePath( $filePath );
	}

	public function run(): void
	{
		if( 0 === strlen( $this->filePath ) )
			die( 'No file selected' );

		$rawMail	= (string) file_get_contents( $this->filePath );
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

	/**
	 *	@param		array			$list
	 *	@param		string			$key
	 *	@param		string|NULL		$value
	 *	@return		void
	 */
	protected function addListItem( array & $list, string $key, ?string $value ): void
	{
		$value	= htmlentities( (string) $value, ENT_QUOTES, 'UTF-8' );
		$list[]	= Tag::create( 'li', $key.': '.$value );
	}

	protected function renderListMain( Message $object ): string
	{
		$listMain	= array();
		$this->addListItem( $listMain, 'Subject', $object->getSubject() );
		$this->addListItem( $listMain, 'Date', current( $object->getHeaders()->getFieldsByName( 'Date' ) )->getValue() );
		$this->addListItem( $listMain, 'From', NULL !== $object->getSender() ? $object->getSender()->get() : '-' );
		foreach( $object->getRecipients() as $type => $recipients ){
			foreach( $recipients as $recipient ){
				$this->addListItem( $listMain, ucfirst( $type ), $recipient->get() );
			}
		}
		$headerReturnPath	= $object->getHeaders()->getFieldsByName( 'Return-Path' );
		$headerDeliveredTo	= $object->getHeaders()->getFieldsByName( 'Delivered-To' );
		if( 0 !== count( $headerDeliveredTo ) )
			$this->addListItem( $listMain, 'Delivered-To', $headerDeliveredTo[0]->getValue() );
		if( 0 !== count( $headerReturnPath ) )
			$this->addListItem( $listMain, 'Return', $headerReturnPath[0]->getValue() );
		$this->addListItem( $listMain, 'ID', current( $object->getHeaders()->getFieldsByName( 'Message-ID' ) )->getValue() );
		return Tag::create( 'ul', $listMain );
	}

	protected function renderListParts( Message $object ): string
	{
		$parts		= $object->getParts( TRUE );
		$listParts		= array();
		foreach( $parts as $nr => $part ){
			$list2		= array();
			$listParts[]	= Tag::create( 'h4', 'Part #'.( $nr + 1 ) );
			$this->addListItem( $list2, 'Type', ObjectConstant::staticGetKeyByValue( '\CeusMedia\Mail\Message\Part', $part->getType(), 'TYPE_' ) );
			if( $part->getCharset() )
				$this->addListItem( $list2, 'Charset', $part->getCharset() );
			if( $part->getEncoding() )
				$this->addListItem( $list2, 'Encoding', $part->getEncoding() );
			if( $part->getFormat() )
				$this->addListItem( $list2, 'Format', $part->getFormat() );
			$this->addListItem( $list2, 'Class', (string) get_class( $part ) );
			if( $part->isInlineImage() )
				$this->addListItem( $list2, 'Content-ID', $part->getId() );
			if( $part->isAttachment() ){
				if( $part->getFileName() )
					$this->addListItem( $list2, 'Filename', $part->getFileName() );
				if( $part->getFileSize() )
					$this->addListItem( $list2, 'Filesize', $part->getFileSize() );
			}
			$listParts[]	= Tag::create( 'ul', $list2 );
			if( $part->isText() ){
				$listParts[]	= Tag::create( 'h5', 'Content' );
				$listParts[]	= Tag::create( 'xmp', $part->getContent() );
			}
			else if( $part->isHTML() ){
				$listParts[]	= Tag::create( 'h5', 'Content' );
				$listParts[]	= Tag::create( 'xmp', $part->getContent() );
			}
		}
		return join( $listParts );
	}

	protected function renderListMore( Message $object ): string
	{
		$listMore		= [];
		$blacklist		= ['Date', 'From', 'To', 'Subject', 'Message-ID', 'Return-Path', 'Delivered-To'];
		foreach( $object->getHeaders()->getFields() as $header ){
			if( in_array( $header->getName(), $blacklist, TRUE ) )
				continue;
			if( FALSE !== preg_match( '/^X-/', $header->getName() ) )
				continue;
			if( 0 === strlen( trim( $header->getValue() ) ) )
				continue;
			$this->addListItem( $listMore, $header->getName(), $header->getValue() );
		}
		return Tag::create( 'ul', $listMore );
	}

	protected function renderListExtended( Message $object ): string
	{
		$listExtended	= [];
		foreach( $object->getHeaders()->getFields() as $header ){
			if( FALSE === preg_match( '/^X-/', $header->getName() ) )
				continue;
			if( 0  === strlen( trim( $header->getValue() ) ) )
				continue;
			$this->addListItem( $listExtended, $header->getName(), $header->getValue() );
		}
		return Tag::create( 'ul', $listExtended );
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
