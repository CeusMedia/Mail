<?php
namespace CeusMedia\Mail;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;

class Client
{
	protected $mailbox;
	protected $transport;

	public static function getInstance()
	{
		return new self();
	}

	public function createAddress(): Address
	{
		return Address::getInstance();
	}

	public function createMessage(): Message
	{
		return Message::getInstance();
	}

	public function createSearch( $conditions = array() ): MailboxSearch
	{
		return MailboxSearch::getInstance()->applyConditions( $conditions );
	}

	/**
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

	public function getMailbox(): Mailbox
	{
		if( !$this->mailbox )
			throw new \RuntimeException( 'No mailbox set' );
		return $this->mailbox;
	}

	public function getTransport(): SMTP
	{
		if( !$this->transport )
			throw new \RuntimeException( 'No transport set' );
		return $this->transport;
	}

	public function sendMessage( Message $message )
	{
		if( !$this->transport )
			throw new \RuntimeException( 'No transport set' );
		$this->transport->send( $message );
		return $this;
	}

	public function setMailbox( Mailbox $mailbox )
	{
		$this->mailbox		= $mailbox;
		return $this;
	}

	public function setTransport( Transport\SMTP $transport )
	{
		$this->transport	= $transport;
		return $this;
	}
}
