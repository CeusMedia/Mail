<?php
/**
 *	Mail message parser.
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;

use \CeusMedia\Mail\Address\Collection\Parser as AddressCollectionParser;
use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use \CeusMedia\Mail\Message\Part as MessagePart;
use \CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use \CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use \CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use \CeusMedia\Mail\Message\Part\Text as MessagePartText;

/**
 *	Mail message parser.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
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
	public static function create(): self
	{
		return new self();
	}

	public function parse( $content )
	{
		$message	= new Message();
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );

		$headers	= MessageHeaderParser::create()->parse( $parts[0] );
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
					$addresses	= AddressCollectionParser::create()->parse( $field->getValue() );
					foreach( $addresses as $address )
						$message->addRecipient( $address );
					break;
			}
		}
		$contentType	= $headers->getField( 'Content-Type' )->getValue();
		if( preg_match( "/multipart/", $contentType ) ){						//  is multipart message
			$this->parseMultipartBody( $message, $content );					//  parse multipart containers
		}
		else{
			$message->addPart( $this->parseAtomicBodyPart( $content ) );		//  directly parse mail content as part
		}
		return $message;
	}

	/*  --  PROTECTED  --  */
	protected function getCharsetFromContentType( $contentType )
	{
		$parts	= explode( ";", $contentType );
		foreach( $parts as $part ){
			$pair	= explode( "=", trim( $part ) );
			if( $pair[0] === "charset" )
				return trim( $pair[1] );
		}
		return NULL;
	}

	protected function getMimeFromContentType( $contentType )
	{
		$parts	= explode( ";", $contentType );
		return trim( $parts[0] );
	}

	protected function parseMultipartBody( $message, $content )
	{
		$delim		= Message::$delimiter;
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );
		$headers	= MessageHeaderParser::create()->parse( $parts[0] );
		$body		= $parts[1];
		$contentType	= $headers->getField( 'Content-Type' )->getValue();
		$contentType	= $this->parseAttributedHeaderValue( $contentType );
		$mimeBoundary	= $contentType->attributes->get( 'boundary' );
		if( $mimeBoundary ){
			$lines	= array();
			$status	= 0;
			foreach( preg_split( "/\r?\n/", $body ) as $nr => $line ){
				if( $line === '--'.$mimeBoundary ){
					if( $status === 0 ){
						$status	= 1;
						continue;
					}
					$this->parseMultipartBody( $message, join( $delim, $lines ) );
					$lines	= array();
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

	protected function parseAttributedHeaderValue( $string )
	{
		$string	= trim( preg_replace( "/\r?\n/", "", $string ) );
		$parts	= preg_split( '/\s*;\s*/', $string );
		$value	= array_shift( $parts );
		$list	= array();
		if( $parts ){
			foreach( $parts as $part ){
				if( preg_match( '/=/', $part ) ){
					$p = preg_split( '/\s?=\s?/', $part, 2 );
					if( trim( $p[1][0] ) === '"' )
						$p[1]	= substr( trim( $p[1] ), 1, -1 );
					if( preg_match( '/\*[0-9]$/', $p[0] ) ){
						$label	= preg_replace( '/^(.+)\*[0-9]$/', '\\1', $p[0] );
						if( !isset( $list[$label] ) )
							$list[$label]	= '';
						$list[$label]	.= $p[1];
					}
					else
						$list[$p[0]]	= $p[1];
				}
			}
		}
		return (object) array(
			'value'			=> $value,
			'attributes'	=> new \ADT_List_Dictionary( $list ),
		);
	}

	protected function parseAtomicBodyPart( $content )
	{
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );
		$headers	= MessageHeaderParser::create()->parse( $parts[0] );
		$content	= $parts[1];

		$contentType	= $headers->getField( 'Content-Type' )->getValue();
		$contentType	= $this->parseAttributedHeaderValue( $contentType );
		$mimeType		= $contentType->value;
		$charset		= $contentType->attributes->get( 'charset' );
		$format			= $contentType->attributes->get( 'format' );
		$encoding		= NULL;
		if( $headers->hasField( 'Content-Transfer-Encoding' ) ){
			$encoding	= $headers->getField( 'Content-Transfer-Encoding' )->getValue();
			$content	= MessagePart::decodeContent( $content, $encoding );
		}
		if( $mimeType === 'message/rfc822' ){
			$part	= new MessagePartMail( $content, $charset );
			$part->setMimeType( $mimeType );
			if( $encoding )
				$part->setEncoding( $encoding );
			if( $format )
				$part->setFormat( $format );
			return $part;
		}

		if( $headers->hasField( 'Content-Disposition' ) ){
			$disposition	= $headers->getField( 'Content-Disposition' )->getValue();
			$disposition	= $this->parseAttributedHeaderValue( $disposition );
			$filename		= $disposition->attributes->get( 'filename' );
			if( strtolower( $disposition->value ) === "attachment" ){
				$part	= new MessagePartAttachment();
				$part->setMimeType( $mimeType );
				if( $encoding )
					$part->setEncoding( $encoding );
				$part->setContent( $content );
				if( $format )
					$part->setFormat( $format );
				if( !$filename && $contentType->attributes->has( 'name' ) )
					$filename	= $contentType->attributes->get( 'name' );
				if( $filename )
					$part->setFilename( $filename );

				$dispositionAttributesToCopy	= array(
					'size'				=> 'setFileSize',
					'read-date'			=> 'setFileATime',
					'creation-date'		=> 'setFileCTime',
					'modification-date'	=> 'setFileMTime',
				);
				foreach( $dispositionAttributesToCopy as $key => $method ){
					$value	= $disposition->attributes->get( $key );
					if( preg_match( '/-date$/', $key ) )
						$value	= strtotime( $value );
					if( $value )
						\Alg_Object_MethodFactory::callObjectMethod( $part, $method, array( $value ) );
				}
				return $part;
			}
		}
		switch( strtolower( $mimeType ) ){
			case 'text/html':
				$part	= new MessagePartHTML( $content, $charset );
				$part->setMimeType( $mimeType );
				if( $encoding )
					$part->setEncoding( $encoding );
				if( $format )
					$part->setFormat( $format );
				return $part;
			case 'text/plain':
			default:
				$part	= new MessagePartText( $content, $charset );
				$part->setMimeType( $mimeType );
				if( $encoding )
					$part->setEncoding( $encoding );
				if( $format )
					$part->setFormat( $format );
				return $part;
		}
	}
}
