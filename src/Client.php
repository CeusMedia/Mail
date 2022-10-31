<?php
declare(strict_types=1);

/**
 *	Mail client able to compose, send and read mails.
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP as SmtpTransport;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;
use RuntimeException;

/**
 *	Mail client able to compose, send and read mails.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Client
{
	/**	@var	Mailbox|NULL				$mailbox */
	protected ?Mailbox $mailbox				= NULL;

	/**	@var	SmtpTransport|NULL			$transport */
	protected ?SmtpTransport $transport		= NULL;

	public function createAddress(): Address
	{
		return Address::getInstance();
	}

	public function createMessage(): Message
	{
		return Message::getInstance();
	}

	public function createSearch( array $conditions = [] ): MailboxSearch
	{
		return MailboxSearch::getInstance()->applyConditions( $conditions );
	}

	/**
	 *	@param			array			$conditions
	 *	@param			integer|NULL	$limit
	 *	@param			integer|NULL	$offset
	 *	@return			array
	 *	@deprecated		use search with mailbox search instance instead
	 *	@todo			to be removed in 2.6
	 *	@codeCoverageIgnore
	 *	@noinspection	PhpDocMissingThrowsInspection
	 */
	public function find( array $conditions, ?int $limit = NULL, ?int $offset = NULL ): array
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method search with mailbox search instance, instead' );
		$mailbox	= $this->getMailbox();
		return $mailbox->search( $conditions );
	}

	public function search( MailboxSearch $search ): array
	{
		$mailbox	= $this->getMailbox();
		return $mailbox->performSearch( $search );
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
	 *	Returns set connectable mailbox instance.
	 *	@access		public
	 *	@return		Mailbox				Set connectable mailbox instance
	 *	@throws		RuntimeException	if no mailbox instance has been set
	 */
	public function getMailbox(): Mailbox
	{
		if( NULL === $this->mailbox )
			throw new RuntimeException( 'No mailbox set' );
		return $this->mailbox;
	}

	/**
	 *	Returns set SMTP transport instance.
	 *	@access		public
	 *	@return		SmtpTransport		Set SMTP transport instance
	 *	@throws		RuntimeException	if no SMTP transport instance has been set
	 */
	public function getTransport(): SmtpTransport
	{
		if( NULL === $this->transport )
			throw new RuntimeException( 'No transport set' );
		return $this->transport;
	}

	public function sendMessage( Message $message ): self
	{
		if( NULL === $this->transport )
			throw new RuntimeException( 'No transport set' );
		$this->transport->send( $message );
		return $this;
	}

	/**
	 *	Set connectable mailbox instance.
	 *	@access		public
	 *	@param		Mailbox	$mailbox
	 *	@return		self
	 */
	public function setMailbox( Mailbox $mailbox ): self
	{
		$this->mailbox		= $mailbox;
		return $this;
	}

	/**
	 *	Set instance of SMTP transport.
	 *	@access		public
	 *	@param		SmtpTransport	$transport
	 *	@return		self
	 */
	public function setTransport( SmtpTransport $transport ): self
	{
		$this->transport	= $transport;
		return $this;
	}
}
