<?php
declare(strict_types=1);

/**
 *	Renderer for mails.
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

use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Part\Attachment as MessagePartAttachment;
use CeusMedia\Mail\Message\Part\HTML as MessagePartHTML;
use CeusMedia\Mail\Message\Part\InlineImage as MessagePartInlineImage;
use CeusMedia\Mail\Message\Part\Mail as MessagePartMail;
use CeusMedia\Mail\Message\Part\Text as MessagePartText;

use RuntimeException;

use function array_pop;
use function count;
use function join;
use function md5;
use function microtime;
use function rtrim;
use function sha1;
use function strlen;
use function time;
use function trim;

/**
 *	Renderer for mails.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Renderer
{
	/**	@var	string		$encodingSubject */
	public static $encodingSubject	= 'quoted-printable';

	/**
	 *	Static constructor.
	 *	@access			public
	 *	@static
	 *	@return			self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 */
	public static function create(): self
	{
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method getInstance instead' );
		return new self();
	}

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
	 *	@see		https://stackoverflow.com/questions/40389103/create-html-mail-with-inline-image-and-pdf-attachment/40420648#40420648
	 */
	public static function render( Message $message ): string
	{
		if( 0 === count( $message->getParts( TRUE ) ) )
			throw new RuntimeException( 'No content part set' );
		$subject	= trim( $message->getSubject() ?? '' );
		if( 0 === strlen( $subject ) )
			throw new RuntimeException( 'No subject set' );

		$headers	= $message->getHeaders();
		if( !$headers->hasField( 'Message-ID' ) ){
			$server	= $_SERVER['SERVER_NAME'] ?? 'localhost';
			$headers->setFieldPair( 'Message-ID', '<'.sha1( microtime() ).'@'.$server.'>' );
		}
		if( !$headers->hasField( 'Date' ) )
			$headers->setFieldPair( 'Date', date( 'D, d M Y H:i:s O', time() ) );

		$encoder	= Encoding::getInstance();
		$encodedSubject	= $encoder->encodeIfNeeded( $subject, self::$encodingSubject );
		$headers->setFieldPair( 'Subject', $encodedSubject );

		$headers->setFieldPair( 'MIME-Version', '1.0' );
		$headers->setFieldPair( 'X-Mailer', $message->getUserAgent() );

		if( 1 === count( $message->getParts( TRUE ) ) ){						//  no multipart message
			$parts	= $message->getParts( TRUE );								//  get parts
			$part	= array_pop( $parts );										//  get solo part
			return $part->render( MessagePart::SECTION_ALL, $headers );			//  render part and apply part headers as message headers
		}

		$parts	= (object) [
			'body'	=> (object) [
				'html'	=> NULL,
				'text'	=> NULL,
			],
		'files'		=> [],
			'images'	=> [],
		];
		foreach( $message->getParts( TRUE ) as $part ){
			if( $part instanceof MessagePartHTML )
				$parts->body->html	= $part;
			else if( $part instanceof MessagePartText )
				$parts->body->text	= $part;
			else if( $part instanceof MessagePartInlineImage )
				$parts->images[]	= $part;
			else if( $part instanceof MessagePartAttachment )
				$parts->files[]		= $part;
			else if( $part instanceof MessagePartMail )
				$parts->files[]		= $part;
		}

		$delim			= Message::$delimiter;
		$mimeBoundary	= '------'.md5( (string) ( microtime( TRUE ) + 0 ) );	//  mixed multipart boundary
		$mimeBoundary1	= '------'.md5( (string) ( microtime( TRUE ) + 1 ) );	//  related multipart boundary
		$mimeBoundary2	= '------'.md5( (string) ( microtime( TRUE ) + 2 ) );	//  alternative multipart boundary
		$headers->setFieldPair( 'Content-Type', 'multipart/related;'.$delim.' boundary="'.$mimeBoundary.'"' );
		$contents		= [ 'This is a multi-part message in MIME format.' ];
		$bodyParts		= $message->getParts( FALSE, FALSE );
		if( count( $bodyParts ) > 1 ){							//  alternative content parts
			$contents[]	= '--'.$mimeBoundary;
			$contents[]	= 'Content-Type: multipart/alternative; boundary="'.$mimeBoundary1.'"';
			$contents[]	= '';
			foreach( $bodyParts as $part )
				$contents[]	= '--'.$mimeBoundary1.$delim.rtrim( $part->render() ).$delim;
			$contents[]	= '--'.$mimeBoundary1.'--'.$delim;
		}
		else{
			foreach( $bodyParts as $part )
				$contents[]	= '--'.$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		}
		foreach( $parts->images as $part )
			$contents[]	= '--'.$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		foreach( $parts->files as $part )
			$contents[]	= '--'.$mimeBoundary.$delim.rtrim( $part->render() ).$delim;
		$contents[]	= '--'.$mimeBoundary.'--'.$delim;
		return $headers->toString( TRUE ).$delim.$delim.join( $delim, $contents );
	}
}
