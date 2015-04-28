<?php
/**
 *	Sends Mail using PHPs mail function and local SMTP server.
 *
 *	Copyright (c) 2007-2014 Christian Würker (ceusmedia.de)
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
 *	@category		cmModules
 *	@package		Mail.Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id: Default.php5 1080 2013-07-23 01:56:47Z christian.wuerker $
 */
/**
 *	Sends Mails of different Types.
 *	@category		cmModules
 *	@package		Mail.Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id: Default.php5 1080 2013-07-23 01:56:47Z christian.wuerker $
 */
class CMM_Mail_Transport_Default
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
		$this->mailer	= 'CMM_Mail_Message/'.CMC_VERSION;
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
			throw new InvalidArgumentException( 'Mail injection attempt detected' );
	}

	/**
	 *	Sends Mail.
	 *	@access		public
	 *	@param		CMM_Mail_Message	$mail		Mail message object
	 *	@param		array				$parameters	Additional mail parameters
	 *	@return		void
	 *	@throws		RuntimeException|InvalidArgumentException
	 */
	public function send( $mail, $parameters = array() )
	{
		$body		= $mail->getBody();
		$headers	= $mail->getHeaders();
		$receiver	= $mail->getReceiver();
		$subject	= $mail->getSubject();

		//  --  VALIDATION & SECURITY CHECK  --  //
		$this->checkForInjection( $receiver );
		$this->checkForInjection( $subject );
		if( !$headers->hasField( 'From' ) )
			throw new InvalidArgumentException( 'No mail sender defined' );
		if( !$receiver )
			throw new InvalidArgumentException( 'No mail receiver defined' );
		if( !$subject )
			throw new InvalidArgumentException( 'No mail subject defined' );
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
			throw new RuntimeException( 'Mail could not been sent' );
	}


	/**
	 *	Sends Mail statically.
	 *	@access		public
	 *	@static
	 *	@param		CMM_Mail_Message	$mail		Mail Object
	 *	@param		array		$parameters	Additional mail parameters
	 *	@return		void
	 */
	public static function sendMail( $mail, $parameters = array() )
	{
		$transport	= new CMM_Mail_Transport_Default();
		$transport->send( $mail, $parameters );
	}
}
?>