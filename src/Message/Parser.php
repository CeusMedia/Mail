<?php
declare(strict_types=1);

/**
 *	Mail message parser.
 *
 *	Copyright (c) 2007-2022 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use CeusMedia\Common\Alg\Obj\MethodFactory as MethodFactory;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection\Parser as AddressCollectionParser;
use CeusMedia\Mail\Conduct\RegularStringHandling;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use CeusMedia\Mail\Message\Part\Text as MessagePartText;

use RuntimeException;

use function in_array;
use function join;
use function strlen;
use function strtolower;
use function strtotime;
use function strtoupper;
use function trim;

/**
 *	Mail message parser.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			finish: parse mail headers too
 */
class Parser
{
	use RegularStringHandling;

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 *	Parse raw mail content and return message object.
	 *	@access		public
	 *	@param		string		$content		Content to parse
	 *	@return		Message
	 */
	public function parse( $content ): Message
	{
		$message	= new Message();

		$parts		= self::regSplit( "/\r?\n\r?\n/", $content, 2,
			'Splitting message into parts failed'
		);
		$headers	= MessageHeaderParser::getInstance()->parse( $parts[0] );
		foreach( $headers->getFields() as $field ){
			$message->addHeader( $field );
			switch( strtolower( $field->getName() ) ){
				case 'from':
					$message->setSender( $field->getValue() );
					break;
				case 'subject':
					$message->setSubject( $field->getValue() );
					break;
				case 'to':
				case 'cc':
				case 'bcc':
					$addresses	= AddressCollectionParser::getInstance()->parse( $field->getValue() );
					/** @var Address $address */
					foreach( $addresses->filter() as $address )
						$message->addRecipient( $address, NULL, $field->getName() );
					break;
			}
		}
		if( $headers->hasField( 'Content-Type' ) ){
			$mimeType	= $headers->getField( 'Content-Type' )->getValue();
			$attributes	= $headers->getField( 'Content-Type' )->getAttributes();
			if( self::regMatch( "/multipart/", $mimeType ) ){					//  is multipart message
				$this->parseMultipartBody( $message, $content );					//  parse multipart containers
				return $message;
			}
		}

		$message->addPart( $this->parseAtomicBodyPart( $content ) );			//  directly parse mail content as part
		return $message;
	}

	/*  --  PROTECTED  --  */

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string					$content
	 *	@param		MessageHeaderField		$contentType
	 *	@param		MessageHeaderField		$disposition
	 *	@param		string					$mimeType
	 *	@param		string|NULL				$encoding
	 *	@param		string|NULL				$format
	 *	@return		MessagePartAttachment
	 */
	protected static function createAttachmentPart( string $content, MessageHeaderField $contentType, MessageHeaderField $disposition, string $mimeType, ?string $encoding = NULL, ?string $format = NULL ): MessagePartAttachment
	{
		$part	= new MessagePartAttachment();
		$part->setMimeType( $mimeType );
		if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
			$part->setEncoding( $encoding );
		$part->setContent( $content );
		if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
			$part->setFormat( $format );

		$filename	= $disposition->getAttribute( 'filename' );
		if( NULL === $filename && NULL !== $contentType->getAttribute( 'name' ) )
			$filename	= $contentType->getAttribute( 'name' );
		if( NULL !== $filename )
			$part->setFileName( $filename );

		$dispositionAttributesToCopy	= [
			'size'				=> 'setFileSize',
			'read-date'			=> 'setFileATime',
			'creation-date'		=> 'setFileCTime',
			'modification-date'	=> 'setFileMTime',
		];
		$methodFactory	= new MethodFactory( $part );
		foreach( $dispositionAttributesToCopy as $key => $method ){
			$value	= $disposition->getAttribute( $key );
			if( is_null( $value ) )
				continue;
			if( self::regMatch( '/-date$/', $key ) )
				$value	= strtotime( $value );
			if( FALSE !== $value )
				$methodFactory->setMethod( $method, array( $value ) )->call();
		}
		return $part;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		MessageHeaderSection	$headers
	 *	@param		string					$content
	 *	@param		MessageHeaderField		$contentType
	 *	@param		string					$mimeType
	 *	@param		string|NULL				$encoding
	 *	@param		string|NULL				$format
	 *	@return		MessagePart|NULL		Instance of MessagePartAttachment or MessagePartInlineImage
	 */
	protected static function createDispositionPart( MessageHeaderSection $headers, string $content, MessageHeaderField $contentType, string $mimeType, ?string $encoding = NULL, ?string $format = NULL ): ?MessagePart
	{
		$disposition		= $headers->getField( 'Content-Disposition' );
		$dispositionType	= strtoupper( $disposition->getValue() );
		if( $dispositionType === 'INLINE' && $headers->hasField( 'Content-Id' ) ){
			return self::createInlineImagePart(
				$headers,
				$content,
				$contentType,
				$disposition,
				$mimeType,
				$encoding,
				$format
			);
		}

		$filename	= $disposition->getAttribute( 'filename' );
		$couldBeAttachment	= in_array( $dispositionType, [ 'INLINE', 'ATTACHMENT' ], TRUE );
		if( $couldBeAttachment && NULL !== $filename ){
			return self::createAttachmentPart(
				$content,
				$contentType,
				$disposition,
				$mimeType,
				$encoding,
				$format
			);
		}
		return NULL;
	}

	protected static function createHTMLPart( string $content, string $charset, ?string $encoding = NULL, ?string $format = NULL ): MessagePartHTML
	{
		$part	= new MessagePartHTML( $content, $charset );
		if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
			$part->setEncoding( $encoding );
		if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
			$part->setFormat( $format );
		return $part;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		MessageHeaderSection	$headers
	 *	@param		string					$content
	 *	@param		MessageHeaderField		$contentType
	 *	@param		MessageHeaderField		$disposition
	 *	@param		string					$mimeType
	 *	@param		string|NULL				$encoding
	 *	@param		string|NULL				$format
	 *	@return		MessagePartInlineImage
	 */
	protected static function createInlineImagePart( MessageHeaderSection $headers, string $content, MessageHeaderField $contentType, MessageHeaderField $disposition, string $mimeType, ?string $encoding = NULL, ?string $format = NULL ): MessagePartInlineImage
	{
		$id		= $headers->getField( 'Content-Id' )->getValue();
		$part	= new MessagePartInlineImage( trim( $id, '<>' ) );

		$part->setMimeType( $mimeType );
		if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
			$part->setEncoding( $encoding );
		$part->setContent( $content );
		if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
			$part->setFormat( $format );

		$filename	= $disposition->getAttribute( 'filename' );
		if( NULL === $filename && NULL !== $contentType->getAttribute( 'name' ) )
			$filename	= $contentType->getAttribute( 'name' );
		if( NULL !== $filename )
			$part->setFileName( $filename );

		$dispositionAttributesToCopy	= [
			'size'				=> 'setFileSize',
			'read-date'			=> 'setFileATime',
			'creation-date'		=> 'setFileCTime',
			'modification-date'	=> 'setFileMTime',
		];
		$methodFactory	= new MethodFactory( $part );
		foreach( $dispositionAttributesToCopy as $key => $method ){
			$value	= $disposition->getAttribute( $key );
			if( is_null( $value ) )
				continue;
			if( self::regMatch( '/-date$/', $key ) )
				$value	= strtotime( $value );
			if( FALSE !== $value )
				$methodFactory->setMethod( $method, array( $value ) )->call();
		}
		return $part;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string					$content
	 *	@param		string					$charset
	 *	@param		string|NULL				$encoding
	 *	@param		string|NULL				$format
	 *	@return		MessagePartMail
	 */
	protected static function createMailPart( string $content, string $charset, ?string $encoding = NULL, ?string $format = NULL ): MessagePartMail
	{
		$part	= new MessagePartMail( $content, $charset );
		if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
			$part->setEncoding( $encoding );
		if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
			$part->setFormat( $format );
		return $part;
	}

	protected static function createTextPart( string $content, string $charset, ?string $encoding = NULL, ?string $format = NULL ): MessagePartText
	{
		$part	= new MessagePartText( $content, $charset );
		if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
			$part->setEncoding( $encoding );
		if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
			$part->setFormat( $format );
		return $part;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$content			...
	 *	@return		MessagePart
	 */
	protected function parseAtomicBodyPart( string $content ): MessagePart
	{
		$parts		= self::regSplit( "/\r?\n\r?\n/", $content, 2,
			'Splitting multipart message body into parts failed'
		);
		$content	= $parts[1];
		$headers	= MessageHeaderParser::getInstance()->parse( $parts[0] );

		$contentType	= $headers->getField( 'Content-Type' );
		$mimeType		= $contentType->getValue();
		$charset		= $contentType->getAttribute( 'charset' ) ?? 'UTF-8';
		$format			= $contentType->getAttribute( 'format' );
		$encoding		= NULL;
		if( $headers->hasField( 'Content-Transfer-Encoding' ) ){
			$encoding	= $headers->getField( 'Content-Transfer-Encoding' )->getValue();
			$content	= MessagePart::decodeContent( $content, $encoding, $charset );
		}

		if( $mimeType === 'message/rfc822' )
			return self::createMailPart( $content, $charset, $encoding, $format );

		if( $headers->hasField( 'Content-Disposition' ) ){
			$field = $headers->getField( 'Content-Disposition' );
			$part	= self::createDispositionPart( $headers, $content, $contentType, $mimeType, $encoding, $format );
			if( NULL !== $part )
				return $part;
		}
		switch( strtolower( $mimeType ) ){
			case 'text/html':
				return self::createHTMLPart( $content, $charset, $encoding, $format );
			case 'text/plain':
			default:
				return self::createTextPart( $content, $charset, $encoding, $format );
		}
	}

	/**
	 *	Parses complete body of a multipart message.
	 *	Adds found body parts to given message object.
	 *	Supports nested multiparts by working recursivly.
	 *	@access		protected
	 *	@param		Message		$message		...
	 *	@param		string		$content		...
	 *	@return		void
	 */
	protected function parseMultipartBody( Message $message, string $content )
	{
		$delim		= Message::$delimiter;
		$bodyParts	= self::regSplit( "/\r?\n\r?\n/", $content, 2,
			'Splitting multipart message body into parts failed'
		);

		$headers	= MessageHeaderParser::getInstance()->parse( $bodyParts[0] );
		$body		= $bodyParts[1];

		if( !$headers->hasField( 'Content-Type' ) )
			throw new RuntimeException( 'Multipart has no content type header' );

		$contentType	= $headers->getField( 'Content-Type' );
		$mimeBoundary	= $contentType->getAttribute( 'boundary' );

		if( NULL !== $mimeBoundary ){
			$lines	= [];
			$status	= 0;
			$bodyLines	= self::regSplit( "/\r?\n/", $body, NULL,
				'Splitting multipart message body into lines failed'
			);
			foreach( $bodyLines as $nr => $line ){
				if( $line === '--'.$mimeBoundary ){
					if( $status === 0 ){
						$status	= 1;
						continue;
					}
					$this->parseMultipartBody( $message, join( $delim, $lines ) );
					$lines	= [];
				}
				else if( $line === '--'.$mimeBoundary.'--' ){
					$this->parseMultipartBody( $message, join( $delim, $lines ) );
					break;
				}
				else if( $status === 1 )
					$lines[]	= $line;
			}
		}
		else{
			$part	= $this->parseAtomicBodyPart( $content );
			$message->addPart( $part );
		}
	}
}
