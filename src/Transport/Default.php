<?php
/**
 *	Sends Mail using PHPs mail function and local SMTP server.
 *
 *	Copyright (c) 2007-2015 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport;
/**
 *	Sends Mails of different Types.
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Default
{
	/**	@var		string		$mailer		Mailer Agent */
	public $mailer;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$mailer		Mailer Agent
	 *	@return		void
	 */
	public function __construct( $mailer = NULL )
	{
		$this->mailer	= 'CeusMedia/Mail/'.CMC_VERSION;
		if( is_string( $mailer ) && strlen( trim( $mailer ) ) )
			$this->mailer	= $mailer;
	}

	/**
	 *	Checks a Header Value for possible Mail Injection and throws Exception.
	 *	@access		protected
	 *	@param		string		$value		Header Value
	 *	@return		void
	 *	@throws		InvalidArgumentException
	 */
	protected function checkForInjection( $value )
	{
		if( preg_match( '/(\r|\n)/', $value ) )
			throw new \InvalidArgumentException( 'Mail injection attempt detected' );
	}

	/**
	 *	Sends Mail.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Message	$message		Mail message object
	 *	@param		array					$parameters	Additional mail parameters
	 *	@return		void
	 *	@throws		RuntimeException|InvalidArgumentException
	 */
	public function send( \CeusMedia\Mail\Message $message, $parameters = array() )
	{
		$body		= \CeusMedia\Mail\Renderer::render();
		$headers	= $message->getHeaders();
		$receivers	= $message->getRecipients();
		$subject	= $message->getSubject();

		//  --  VALIDATION & SECURITY CHECK  --  //
		$this->checkForInjection( $receiver );
		$this->checkForInjection( $subject );
		if( !$headers->hasField( 'From' ) )
			throw new \InvalidArgumentException( 'No mail sender defined' );
		if( !$receivers )
			throw new \InvalidArgumentException( 'No mail receiver defined' );
		if( !$subject )
			throw new \InvalidArgumentException( 'No mail subject defined' );
		$subject	= "=?UTF-8?B?".base64_encode( $subject )."?=";

/*		foreach( $headers as $key => $value )
		{
			$this->checkForInjection( $key );
			$this->checkForInjection( $value );
		}
*/
		//  --  HEADERS  --  //
//		if( $this->mailer )
		$headers->setFieldPair( 'X-Mailer', $this->mailer, TRUE );
		$headers->setFieldPair( 'Date', date( 'r' ), TRUE );

		if( is_array( $parameters ) )
			$parameters	= implode( PHP_EOL, $parameters );

		if( !mail( $receiver, $subject, $body, $headers->toString(), $parameters ) )
			throw new \RuntimeException( 'Mail could not been sent' );
	}


	/**
	 *	Sends Mail statically.
	 *	@access		public
	 *	@static
	 *	@param		\CeusMedia\Mail\Message	$message		Mail Object
	 *	@param		array		$parameters	Additional mail parameters
	 *	@return		void
	 */
	public static function sendMail( \CeusMedia\Mail\Message $message, $parameters = array() )
	{
		$transport	= new \CeusMedia\Mail\Transport\Default();
		$transport->send( $message, $parameters );
	}
}
?>
