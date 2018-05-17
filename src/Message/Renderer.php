<?php
/**
 *	Renderer for mails.
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
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message;
/**
 *	Renderer for mails.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Renderer{

	static public $encodingSubject	= 'quoted-printable';

	/**
	 *	@see		https://stackoverflow.com/questions/40389103/create-html-mail-with-inline-image-and-pdf-attachment/40420648#40420648
	 */
	static public function render( \CeusMedia\Mail\Message $message ){
		if( !count( $message->getParts( TRUE ) ) )
			throw new \RuntimeException( "No content part set" );
		if( !strlen( trim( $message->getSubject() ) ) )
			throw new \RuntimeException( "No subject set" );

		$headers	= $message->getHeaders();
		if( !$headers->hasField( 'Message-ID' ) ){
			$server	= empty( $_SERVER['SERVER_NAME'] ) ? 'localhost' : $_SERVER['SERVER_NAME'];
			$headers->setFieldPair( 'Message-ID', "<".sha1( microtime() )."@".$server.">" );
		}
		if( !$headers->hasField( 'Date' ) )
			$headers->setFieldPair( 'Date', date( "D, d M Y H:i:s O", time() ) );
		$headers->setFieldPair( 'Subject', $message->getSubject( self::$encodingSubject ) );
		$headers->setFieldPair( 'MIME-Version', '1.0' );
		$headers->addFieldPair( 'X-Mailer', $message->getUserAgent() );

		if( count( $message->getParts( TRUE ) ) === 1 ){						//  no multipart message
			$parts	= $message->getParts( TRUE );								//  get parts
			$part	= array_pop( $parts );										//  get solo part
			return $part->render( $headers );									//  render part and apply part headers as message headers
		}


		$parts	= (object) array(
			'body'	=> (object) array(
				'html'	=> NULL,
				'text'	=> NULL,
			),
			'files'		=> array(),
			'images'	=> array(),
		);
		foreach( $message->getParts( TRUE ) as $part ){
			if( $part instanceof \CeusMedia\Mail\Message\Part\HTML )
				$parts->body->html	= $part;
			else if( $part instanceof \CeusMedia\Mail\Message\Part\Text )
				$parts->body->text	= $part;
			else if( $part instanceof \CeusMedia\Mail\Message\Part\InlineImage )
				$parts->images[]	= $part;
			else if( $part instanceof \CeusMedia\Mail\Message\Part\Attachment )
				$parts->files[]	= $part;
			else if( $part instanceof \CeusMedia\Mail\Message\Part\Mail )
				$parts->files[]	= $part;
		}

		$delim			= \CeusMedia\Mail\Message::$delimiter;
		$mimeBoundary	= "------".md5( microtime( TRUE ) );					//  mixed multipart boundary
		$mimeBoundary1	= "------".md5( microtime( TRUE ) + 1 );				//  related multipart boundary
		$mimeBoundary2	= "------".md5( microtime( TRUE ) + 2 );				//  alternative multipart boundary
		$headers->setFieldPair( 'Content-Type', 'multipart/related;'.$delim.' boundary="'.$mimeBoundary.'"' );
		$contents	= array(
			"This is a multi-part message in MIME format.",
		);
		if( count( $message->getParts( FALSE ) ) > 1 ){							//  alternative content parts
			$contents[]	= "--".$mimeBoundary;
			$contents[]	= 'Content-Type: multipart/alternative; boundary="'.$mimeBoundary1.'"';
			$contents[]	= "";
			foreach( $message->getParts( FALSE ) as $part )
				$contents[]	= "--".$mimeBoundary1.$delim.rtrim( $part->render() ).$delim;
			$contents[]	= "--".$mimeBoundary1."--".$delim;
		}
		else{
			foreach( $message->getParts( FALSE ) as $part )
				$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		}
		foreach( $parts->images as $part )
			$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		foreach( $parts->files as $part )
			$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary."--".$delim;
		return $headers->toString( TRUE ).$delim.$delim.join( $delim, $contents );

/*		$delim			= \CeusMedia\Mail\Message::$delimiter;
		$mimeBoundary	= "------".md5( microtime( TRUE ) );					//  mixed multipart boundary
		$mimeBoundary1	= "------".md5( microtime( TRUE ) + 1 );				//  related multipart boundary
		$mimeBoundary2	= "------".md5( microtime( TRUE ) + 2 );				//  alternative multipart boundary
		$headers->setFieldPair( 'Content-Type', 'multipart/mixed;'.$delim.' boundary="'.$mimeBoundary.'"' );
		$contents	= array(
			"This is a multi-part message in MIME format.",
			$mimeBoundary,
		);
		$contents[]	= 'Content-Type: multipart/related; boundary="'.$mimeBoundary1.'"';
		$contents[]	= '';
		if( count( $message->getParts( FALSE ) ) > 1 ){							//  alternative content parts
			$contents[]	= "--".$mimeBoundary1;
			$contents[]	= 'Content-Type: multipart/alternative; boundary="'.$mimeBoundary1.'"';
			$contents[]	= "";
			foreach( $message->getParts( FALSE ) as $part )
				$contents[]	= "--".$mimeBoundary2.$delim.rtrim( $part->render() ).$delim;
			$contents[]	= "--".$mimeBoundary2."--".$delim;
		}
		else{
			foreach( $message->getParts( FALSE ) as $part )
				$contents[]	= "--".$mimeBoundary1.$delim.rtrim( $part->render() ).$delim;
		}
		foreach( $parts->images as $part )
			$contents[]	= "--".$mimeBoundary1.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary1."--".$delim;
		foreach( $parts->files as $part )
			$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary."--".$delim;
		return $headers->toString( TRUE ).$delim.$delim.join( $delim, $contents );*/
	}
}
/*
		$delim			= \CeusMedia\Mail\Message::$delimiter;
		$mimeBoundary	= "------".md5( microtime( TRUE ) );					//  mixed multipart boundary
		$mimeBoundary1	= "------".md5( microtime( TRUE ) + 1 );				//  related multipart boundary
		$mimeBoundary2	= "------".md5( microtime( TRUE ) + 2 );				//  alternative multipart boundary
		$headers->setFieldPair( 'Content-Type', 'multipart/related;'.$delim.' boundary="'.$mimeBoundary.'"' );
		$contents	= array(
			"This is a multi-part message in MIME format.",
		);
		if( count( $message->getParts( FALSE ) ) > 1 ){							//  alternative content parts
			$contents[]	= "--".$mimeBoundary;
			$contents[]	= 'Content-Type: multipart/alternative; boundary="'.$mimeBoundary1.'"';
			$contents[]	= "";
			foreach( $message->getParts( FALSE ) as $part )
				$contents[]	= "--".$mimeBoundary1.$delim.rtrim( $part->render() ).$delim;
			$contents[]	= "--".$mimeBoundary1."--".$delim;
		}
		else{
			foreach( $message->getParts( FALSE ) as $part )
				$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		}
		foreach( $parts->images as $part )
			$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary."--".$delim;
		foreach( $parts->files as $part )
			$contents[]	= "--".$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= "--".$mimeBoundary."--".$delim;
		return $headers->toString( TRUE ).$delim.$delim.join( $delim, $contents );
*/
?>
