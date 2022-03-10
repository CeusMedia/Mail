<?php
use CeusMedia\Bootstrap\Icon;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message\Header\Received as ReceivedHeader;
use CeusMedia\Mail\Message;

use Alg_Object_Constant as ObjectConstant;
use UI_HTML_Tag as Tag;

class MailFactsRenderer
{
	protected $file;
	protected $message;

	public function __construct( string $file, Message $message )
	{
		if( !getEnv( 'HTTP_HOST' ) )
			die( "This demo is for browser, only" );
		$this->file		= $file;
		$this->message	= $message;
	}

	public function render(): string
	{
		$listMain				= $this->renderListMain();
		$listParts				= $this->renderListParts();
		$listMore				= $this->renderListMore();
		$listExtended			= $this->renderListExtended();
		$listTransportHistory	= $this->renderListTransportHistory();

		return Tag::create( 'div', [
			Tag::create( 'div', [
				Tag::create( 'div', [
					Tag::create( 'h3', 'Main Headers' ),
					Tag::create( 'div', $listMain ),
					Tag::create( 'h3', 'Parts' ),
					Tag::create( 'div', $listParts ),
					Tag::create( 'h3', 'Transport History' ),
					Tag::create( 'div', $listTransportHistory ),
					Tag::create( 'h3', 'More Headers' ),
					Tag::create( 'div', $listMore ),
					Tag::create( 'h3', 'Extended Headers' ),
					Tag::create( 'div', $listExtended ),
				], ['class' => 'span12'] ),
			], ['class' => 'row-fluid'] ),
		], ['class' => 'container-fluid'] );
	}

	//  --  PROTECTED  --  //

	protected function addListItem( & $list, string $key, $value, array $attributes = [] )
	{
		$sublist	= '';
		if( 0 !== count( $attributes ) ){
			$attr	= [];
			foreach( $attributes as $attrKey => $attrValue ){
				$attrKey	= Tag::create( 'span', $attrKey, ['class' => 'list-item-attribute'] );
				$attr[]		= Tag::create( 'li', $attrKey.' '.$attrValue );
			}
			$sublist	= Tag::create( 'ul', $attr, ['class' => 'unstyled facts-attribute-list'] );
		}
		else if( 'Received' === $key ){
			$attr	= [];
			$received	= ReceivedHeader::parse( $value );
			foreach( $received->toArray() as $attrKey => $attrValue ){
				if( $attrValue instanceof Address )
					$attrValue	= $attrValue->get();
				else if( $attrValue instanceof DateTimeImmutable )
					continue;
				if( 0 === strlen( trim( $attrValue ) ) )
					continue;
				$attrKey	= Tag::create( 'span', $attrKey, ['class' => 'list-item-attribute'] );
				$attr[]		= Tag::create( 'li', $attrKey.' '.$attrValue );
			}
			$value		= $received->getDate()->format( DATE_ATOM );
			$sublist	= Tag::create( 'ul', $attr, ['class' => 'unstyled facts-attribute-list'] );
		}
		$value	= htmlentities( $value, ENT_QUOTES, 'UTF-8' );
		$key	= Tag::create( 'span', $key, ['class' => 'list-item-key'] );
		$list[]	= Tag::create( 'li', $key.' '.$value.$sublist );
	}

	protected function renderListTransportHistory(): string
	{
		$list		= [];
		foreach( $this->message->getHeaders()->getFields() as $header ){
			if( 'Received' !== $header->getName() )
				continue;
			$this->addListItem( $list, $header->getName(), $header->getValue(), $header->getAttributes() );
		}
		return Tag::create( 'ul', array_reverse( $list ), ['class' => 'unstyled facts-header-list'] );
	}

	protected function renderListMain(): string
	{
		$headers	= $this->message->getHeaders();
		$list		= [];
		$this->addListItem( $list, 'Subject', $this->message->getSubject() );
		$this->addListItem( $list, 'Date', current( $headers->getFieldsByName( 'Date' ) )->getValue() );
		$this->addListItem( $list, 'From', $this->message->getSender()->get() );
		foreach( $this->message->getRecipients() as $type => $recipients ){
			foreach( $recipients as $recipient ){
				$this->addListItem( $list, ucfirst( $type ), $recipient->get() );
			}
		}
		$headerReturnPath	= $headers->getFieldsByName( 'Return-Path' );
		$headerDeliveredTo	= $headers->getFieldsByName( 'Delivered-To' );
		if( $headerDeliveredTo )
			$this->addListItem( $list, 'Delivered-To', $headerDeliveredTo[0]->getValue() );
		if( $headerReturnPath )
			$this->addListItem( $list, 'Return', $headerReturnPath[0]->getValue() );
		$this->addListItem( $list, 'ID', current( $headers->getFieldsByName( 'Message-ID' ) )->getValue() );
		return Tag::create( 'ul', $list, ['class' => 'unstyled facts-header-list'] );
	}

	protected function renderListParts(): string
	{
		$parts		= $this->message->getParts( TRUE );
		$listParts		= [];
		foreach( $parts as $nr => $part ){
			$list2		= [];
			$listParts[]	= Tag::create( 'h4', 'Part #'.( $nr + 1 ) );
			$this->addListItem( $list2, 'Type', ObjectConstant::staticGetKeyByValue( '\CeusMedia\Mail\Message\Part', $part->getType(), 'TYPE_' ) );
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
			$listParts[]	= Tag::create( 'ul', $list2, ['class' => 'unstyled facts-header-list'] );
		}
		return join( $listParts );
	}

	protected function renderListMore(): string
	{
		$list		= [];
		foreach( $this->message->getHeaders()->getFields() as $header ){
			if( in_array( $header->getName(), [ 'Date', 'From', 'To', 'Subject', 'Message-ID', 'Return-Path', 'Delivered-To', 'Received' ] ) )
				continue;
			if( preg_match( '/^X-/', $header->getName() ) )
				continue;
			if( !strlen( trim( $header->getValue() ) ) )
				continue;
			$this->addListItem( $list, $header->getName(), $header->getValue(), $header->getAttributes() );
		}
		return Tag::create( 'ul', $list, ['class' => 'unstyled facts-header-list'] );
	}

	protected function renderListExtended(): string
	{
		$list	= [];
		foreach( $this->message->getHeaders()->getFields() as $header ){
			if( !preg_match( '/^X-/', $header->getName() ) )
				continue;
			if( !strlen( trim( $header->getValue() ) ) )
				continue;
			$this->addListItem( $list, $header->getName(), $header->getValue() );
		}
		return Tag::create( 'ul', $list, ['class' => 'unstyled facts-header-list'] );
	}
}
