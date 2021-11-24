<?php
declare(strict_types=1);

/**
 *	Mail message parser.
 *
 *	Copyright (c) 2007-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use CeusMedia\Mail\Address\Collection\Parser as AddressCollectionParser;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\AttributedField as MessageHeaderAttributedField;
use CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use CeusMedia\Mail\Message\Part\Text as MessagePartText;

use Alg_Object_MethodFactory as MethodFactory;
use RuntimeException;

use function in_array;
use function join;
use function preg_match;
use function preg_split;
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
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			finish: parse mail headers too
 */
class Parser
{
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
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );

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
					foreach( $addresses as $address )
						$message->addRecipient( $address, NULL, $field->getName() );
					break;
			}
		}
		if( $headers->hasField( 'Content-Type' ) ){
			$mimeType	= $headers->getField( 'Content-Type' )->getValue();
			if( preg_match( "/multipart/", $mimeType ) ){						//  is multipart message
				$this->parseMultipartBody( $message, $content );				//  parse multipart containers
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
	 *	@param		Message		$message		...
	 *	@param		string		$content		...
	 *	@return		void
	 */
	protected function parseMultipartBody( Message $message, string $content )
	{
		$delim		= Message::$delimiter;
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );
		$headers	= MessageHeaderParser::getInstance()->parse( $parts[0] );
		$body		= $parts[1];

		if( !$headers->hasField( 'Content-Type' ) )
			throw new RuntimeException( 'Multipart has no content type header' );

		$contentType	= $headers->getField( 'Content-Type' );
		$contentType	= MessageHeaderParser::parseAttributedField( $contentType );
		$mimeBoundary	= $contentType->getAttribute( 'boundary' );
		if( NULL !== $mimeBoundary ){
			$lines	= [];
			$status	= 0;
			foreach( preg_split( "/\r?\n/", $body ) as $nr => $line ){
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
		$part	= $this->parseAtomicBodyPart( $content );
		$message->addPart( $part );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$content			...
	 *	@return		MessagePart
	 */
	protected function parseAtomicBodyPart( string $content ): MessagePart
	{
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );
		$content	= $parts[1];
		$headers	= MessageHeaderParser::getInstance()->parse( $parts[0] );

		$contentType	= $headers->getField( 'Content-Type' );
		$contentType	= MessageHeaderParser::parseAttributedField( $contentType );
		$mimeType		= $contentType->getValue();
		$charset		= $contentType->getAttribute( 'charset', 'UTF-8' );
		$format			= $contentType->getAttribute( 'format' );
		$encoding		= NULL;
		if( $headers->hasField( 'Content-Transfer-Encoding' ) ){
			$encoding	= $headers->getField( 'Content-Transfer-Encoding' )->getValue();
			$content	= MessagePart::decodeContent( $content, $encoding, $charset );
		}
		if( $mimeType === 'message/rfc822' ){
			$part	= new MessagePartMail( $content, $charset );
			$part->setMimeType( $mimeType );
			if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
				$part->setEncoding( $encoding );
			if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
				$part->setFormat( $format );
			return $part;
		}

		if( $headers->hasField( 'Content-Disposition' ) ){
			$disposition	= $headers->getField( 'Content-Disposition' );
			$disposition	= MessageHeaderParser::parseAttributedField( $disposition );
			$filename		= $disposition->getAttribute( 'filename' );
			$value			= strtoupper( $disposition->getValue() );
			$isInlineImage	= $value === 'INLINE' && $headers->hasField( 'Content-Id' );
			$isAttachment	= in_array( $value, array( 'INLINE', 'ATTACHMENT' ), TRUE ) && NULL !== $filename;

			if( $isAttachment || $isInlineImage ){
				$part	= new MessagePartAttachment();
				if( $isInlineImage ){
					$id		= $headers->getField( 'Content-Id' )->getValue();
					$part	= new MessagePartInlineImage( $id );
				}
				$part->setMimeType( $mimeType );
				if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
					$part->setEncoding( $encoding );
				$part->setContent( $content );
				if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
					$part->setFormat( $format );
				if( NULL === $filename && NULL !== $contentType->getAttribute( 'name' ) )
					$filename	= $contentType->getAttribute( 'name' );
				if( NULL !== $filename )
					$part->setFilename( $filename );

				$dispositionAttributesToCopy	= array(
					'size'				=> 'setFileSize',
					'read-date'			=> 'setFileATime',
					'creation-date'		=> 'setFileCTime',
					'modification-date'	=> 'setFileMTime',
				);
				$methodFactory	= new MethodFactory( $part );
				foreach( $dispositionAttributesToCopy as $key => $method ){
					$value	= $disposition->getAttribute( $key );
					if( preg_match( '/-date$/', $key ) )
						$value	= strtotime( $value );
					if( $value )
						MethodFactory::callObjectMethod( $part, $method, array( $value ) );
				}
				return $part;
			}
		}
		switch( strtolower( $mimeType ) ){
			case 'text/html':
				$part	= new MessagePartHTML( $content, $charset );
				$part->setMimeType( $mimeType );
				if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
					$part->setEncoding( $encoding );
				if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
					$part->setFormat( $format );
				return $part;
			case 'text/plain':
			default:
				$part	= new MessagePartText( $content, $charset );
				$part->setMimeType( $mimeType );
				if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
					$part->setEncoding( $encoding );
				if( NULL !== $format && 0 !== strlen( trim( $format ) ) )
					$part->setFormat( $format );
				return $part;
		}
	}
}
