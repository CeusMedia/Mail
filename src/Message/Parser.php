<?php
/**
 *	Mail message parser.
 *
 *	Copyright (c) 2007-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;
/**
 *	Mail message parser.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			finish: parse mail headers too
 */
class Parser{

	static protected function getCharsetFromContentType( $contentType ){
		$parts	= explode( ";", $contentType );
		foreach( $parts as $part ){
			$pair	= explode( "=", trim( $part ) );
			if( $pair[0] === "charset" )
				return trim( $pair[1] );
		}
		return NULL;
	}

	static protected function getMimeFromContentType( $contentType ){
		$parts	= explode( ";", $contentType );
		return trim( $parts[0] );
	}

	static public function parse( $content ){
		$message	= new \CeusMedia\Mail\Message();
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );

		$headers	= \CeusMedia\Mail\Message\Header\Parser::parse( $parts[0] );
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
					$addresses	= \CeusMedia\Mail\Address\Collection\Parser::parse( $field->getValue() );
					foreach( $addresses as $address )
						$message->addRecipient( $address );
					break;
			}
		}
		$contentType	= $headers->getField( 'Content-Type' )->getValue();
		if( preg_match( "/multipart/", $contentType ) ){						//  is multipart message
			self::parseMultipartBody( $message, $content );						//  parse multipart containers
		}
		else{
			$message->addPart( self::parseAtomicBodyPart( $content ) );			//  directly parse mail content as part
		}
		return $message;
	}

	static protected function parseMultipartBody( $message, $content ){
		$delim		= \CeusMedia\Mail\Message::$delimiter;
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );
		$headers	= \CeusMedia\Mail\Message\Header\Parser::parse( $parts[0] );
		$body		= $parts[1];

		$contentType	= $headers->getField( 'Content-Type' );
		if( preg_match( '/boundary/', $contentType ) ){
			$mimeBoundary	= preg_replace( "/^.+boundary=\"(.+)\"$/", "\\1", $contentType );
			$lines	= array();
			$status	= 0;
			foreach( preg_split( "/\r?\n/", $body ) as $nr => $line ){
				if( $line === '--'.$mimeBoundary ){
					if( $status === 0 ){
						$status	= 1;
						continue;
					}
					self::parseMultipartBody( $message, join( $delim, $lines ) );
					$lines	= array();
				}
				else if( $line === '--'.$mimeBoundary.'--' ){
					self::parseMultipartBody( $message, join( $delim, $lines ) );
					break;
				}
				else if( $status === 1 )
					$lines[]	= $line;
			}
		}
		else{
			$part	= self::parseAtomicBodyPart( $content );
			$message->addPart( $part );
		}
	}

	static protected function parseAtomicBodyPart( $content ){
		$parts		= preg_split( "/\r?\n\r?\n/", $content, 2 );
		$headers	= \CeusMedia\Mail\Message\Header\Parser::parse( $parts[0] );
		$content	= $parts[1];

		$contentType	= $headers->getField( 'Content-Type' )->getValue();
		$mimeType		= self::getMimeFromContentType( $contentType );
		$charset		= self::getCharsetFromContentType( $contentType );

		if( $headers->hasField( 'Content-Disposition' ) ){
			if( preg_match( "/attachment/", $headers->getField( 'Content-Disposition' )->getValue() ) ){
				$part	= new \CeusMedia\Mail\Message\Part\Attachment();
				$part->setContent( $content, $contentType );
				if( $headers->hasField( 'Content-Transfer-Encoding' ) )
					$part->setEncoding( $headers->getField( 'Content-Transfer-Encoding' )->getValue() );
//				if( $object->format )
//					$part->setFormat( $object->format );
				if( $headers->hasField( 'Content-Description' ) )
					$filename	= $headers->getField( 'Content-Description' );
				else
					$filename	= preg_replace( "/^.+filename=\"(.+)\"$/", "\\1", $headers->getField( 'Content-Disposition' )->getValue() );
				if( $filename )
					$part->setFilename( $filename );
				return $part;
			}
		}
		switch( strtolower( $mimeType ) ){
			case 'text/html':
				$part	= new \CeusMedia\Mail\Message\Part\HTML( $content, $charset );
				if( $headers->hasField( 'Content-Transfer-Encoding' ) )
					$part->setEncoding( $headers->getField( 'Content-Transfer-Encoding' )->getValue() );
//				if( $object->format )
//					$part->setFormat( $object->format );
				return $part;
			case 'text/plain':
			default:
				$part	= new \CeusMedia\Mail\Message\Part\Text( $content, $charset );
				if( $headers->hasField( 'Content-Transfer-Encoding' ) )
					$part->setEncoding( $headers->getField( 'Content-Transfer-Encoding' )->getValue() );
//				if( $object->format )
//					$part->setFormat( $object->format );
				return $part;
		}
	}
}
?>
