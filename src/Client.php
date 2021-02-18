<?php
declare(strict_types=1);

namespace CeusMedia\Mail;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP as SmtpTransport;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;
use RuntimeException;

class Client
{
	/**	@var	Mailbox			$mailbox */
	protected $mailbox;

	/**	@var	SmtpTransport	$transport */
	protected $transport;

	public function createAddress(): Address
	{
		return Address::getInstance();
	}

	public function createMessage(): Message
	{
		return Message::getInstance();
	}

	public function createSearch( array $conditions = array() ): MailboxSearch
	{
		return MailboxSearch::getInstance()->applyConditions( $conditions );
	}

	/**
	 *	@param			array			$conditions
	 *	@param			integer|NULL	$limit
	 *	@param			integer|NULL	$offset
	 *	@return			array
	 *	@deprecated		use search with mailbox search instance instead
	 *	@todo			to be removed
	 */
	public function find( array $conditions, $limit = NULL, $offset = NULL ): array
	{
		$mailbox	= $this->getMailbox();
		return $mailbox->search( $conditions );
	}

	public function search( MailboxSearch $search ): array
	{
		$mailbox	= $this->getMailbox();
		return $mailbox->performSearch( $search );
	}

	public static function getInstance(): self
	{
		return new self();
	}

	public function getMailbox(): Mailbox
	{
		if( NULL === $this->mailbox )
			throw new RuntimeException( 'No mailbox set' );
		return $this->mailbox;
	}

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

	public function setMailbox( Mailbox $mailbox ): self
	{
		$this->mailbox		= $mailbox;
		return $this;
	}

	public function setTransport( SmtpTransport $transport ): self
	{
		$this->transport	= $transport;
		return $this;
	}
}
