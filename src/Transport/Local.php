<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Sends Mail using PHPs mail function and local SMTP server.
 *
 *	Copyright (c) 2007-2025 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport;

use CeusMedia\Common\UI\OutputBuffer;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use CeusMedia\Mail\Message\Renderer;
use CeusMedia\Mail\Util\Str;

use Exception;
use InvalidArgumentException;
use RuntimeException;

use function count;
use function date;
use function implode;
use function mail;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 *	Sends Mails of different Types.
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Local
{
	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct()
	{
	}

	/**
	 *	Sends Mail.
	 *	@access		public
	 *	@param		Message		$message		Mail message object
	 *	@param		array						$parameters	Additional mail parameters
	 *	@return		array<Result>				List of transport results
	 *	@throws		InvalidArgumentException	if sender is not set
	 *	@throws		InvalidArgumentException	if receiver is not set
	 *	@throws		InvalidArgumentException	if subject is not set
	 */
	public function send( Message $message, array $parameters = [] ): array
	{
		$headers	= $message->getHeaders();
		$subject	= $message->getSubject() ?? '';

		//  --  VALIDATION  --  //
		//  no check for line breaks needed: rendering encodes names and replaces line breaks in subject (header injection)
		if( !$headers->hasField( 'From' ) )
			throw new InvalidArgumentException( 'No mail sender defined' );
		if( !$message->hasRecipients() )													//  To may be empty, e.g. for Bcc only
			throw new InvalidArgumentException( 'No mail receiver defined' );
		if( !Str::hasContent( $subject ) )
			throw new InvalidArgumentException( 'No mail subject defined' );

		/** @var array<string,Address> $receivers */
		$receivers	= [];
		foreach( [ 'to', 'cc', 'bcc' ] as $type ){
			/** @var Address $receiver */
			foreach( $message->getRecipientsByType( $type ) as $receiver ){
				$receivers[strtolower( $receiver->getAddress() )]	??= $receiver;
			}
		}

		//  --  HEADERS  --  //
		if( Str::hasContent( $message->getUserAgent() ) )
			$headers->setFieldPair( 'X-Mailer', $message->getUserAgent() );
		$headers->setFieldPair( 'Date', date( 'r' ) );

		//  rendering completes the message headers (subject, content type etc.), mail() needs the body only
		$delimiter	= Message::$delimiter;
		$rendered	= Renderer::render( $message );
		$position	= strpos( $rendered, $delimiter.$delimiter );
		$body		= FALSE !== $position ? substr( $rendered, $position + 2 * strlen( $delimiter ) ) : '';

		//  mail() sets To and Subject itself, other recipients are read from headers by sendmail -t, which removes Bcc
		$to			= [];
		$subject	= '';
		$section	= new MessageHeaderSection();
		foreach( $headers->getFields() as $field ){
			$name	= strtolower( $field->getName() );
			//  To and Subject unfolded, since mail() expects them as single lines; other headers are passed folded.
			//  A very long subject (some hundred bytes encoded) may exceed the line limit of 998 octets then.
			$value	= MessageHeaderEncoding::unfold( $field->getValue() );
			if( 'to' === $name )
				$to[]		= $value;
			else if( 'subject' === $name )
				$subject	= $value;
			else if( 'bcc' !== $name )											//  Bcc is set below, from recipients
				$section->addField( $field );
		}
		$bcc	= [];
		/** @var Address $receiver */
		foreach( $message->getRecipientsByType( 'bcc' ) as $receiver )
			$bcc[]	= $receiver->getAddress();
		if( 0 !== count( $bcc ) )
			$section->addFieldPair( 'Bcc', implode( ', ', $bcc ) );

		if( 0 === count( $to ) )														//  no To recipient, e.g. Bcc only: empty group (RFC 5322, 3.6.3)
			$to[]	= 'undisclosed-recipients:;';

		$error	= NULL;
		$buffer	= new OutputBuffer();
		try{
			$returns	= mail(
				implode( ', ', $to ),
				$subject,
				$body,
				$section->toString( TRUE ),
				implode( ' ', $parameters )
			);
			if( !$returns )
				throw new RuntimeException( $buffer->get() );
		}
		catch( Exception $e ){
			$error	= $e->getMessage();
		}
		$buffer->close();

		$list	= [];
		foreach( $receivers as $receiver ){
			$result	= new Result();
			$result->setReceiver( $receiver );
			$result->setStatus( NULL === $error ? Result::STATUS_SUCCESS : Result::STATUS_ERROR );
			$result->setMessage( $error ?? 'mail sent to '.$receiver->getAddress() );
			$list[]	= $result;
		}
		return $list;
	}


	/**
	 *	Sends Mail statically.
	 *	@access		public
	 *	@static
	 *	@param		Message		$message		Mail Object
	 *	@param		array		$parameters		Additional mail parameters
	 *	@return		array
	 */
	public static function sendMail( Message $message, array $parameters = [] ): array
	{
		$transport	= new self();
		return $transport->send( $message, $parameters );
	}
}
