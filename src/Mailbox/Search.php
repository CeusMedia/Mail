<?php
declare(strict_types=1);

/**
 *	Mailbox search object.
 *
 *	Copyright (c) 2007-2018 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Mailbox;

use CeusMedia\Mail\Mailbox\Mail;
use RuntimeException;

/**
 *	Mailbox search object.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			implement new structure of criteria and flags, see comments at the bottom
 */
class Search
{
	/** @var resource $connection */
	protected $connection;

	/** @var integer $limit */
	protected $limit			= 10;

	/** @var integer $offset */
	protected $offset			= 0;

	/** @var integer $orderSort */
	protected $orderSort		= SORTARRIVAL;

	/** @var bool $orderReverse */
	protected $orderReverse		= TRUE;

	/** @var string $subject */
	protected $subject;

	/** @var string $sender */
	protected $sender;

	public function applyConditions( array $conditions = [] ): self
	{
		foreach( $conditions as $key => $value ){
			switch( strtoupper( $key ) ){
				case 'SUBJECT':
					$this->setSubject( $value );
					break;
				case 'SENDER':
					$this->setSender( $value );
					break;
			}
		}
		return $this;
	}

	/**
	 *	Returns list of mail objects, holding a mail ID and a connection.
	 *	@access		public
	 *	@return		array				List of mail IDs
	 *	@throws		RuntimeException	if no connection is set
	 */
	public function getAll(): array
	{
		$mails		= [];
		foreach( $this->getAllMailIds() as $mailId ){
			$mail	= Mail::getInstance( $mailId )->setConnection( $this->connection );
			$mails[$mailId]	= $mail;
		}
		return $mails;
	}

	/**
	 *	Returns list of mail IDs for defined search criteria.
	 *	@access		public
	 *	@return		array				List of mail IDs
	 *	@throws		RuntimeException	if no connection is set
	 */
	public function getAllMailIds(): array
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'No connection set' );
		$criteria	= $this->renderCriteria();
		$mailIds	= imap_sort(
			$this->connection,
			$this->orderSort,
			$this->orderReverse,
			SE_UID,
			$criteria,
			'UTF-8'
		);
		if( FALSE !== $mailIds )
			return array_slice( $mailIds, $this->offset, $this->limit );
		return [];
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

	public function getCriteria(): array
	{
		$criteria	= [];
		if( 0 < strlen( $this->subject ) )
			$criteria['SUBJECT']	= $this->subject;
		if( 0 < strlen( $this->sender ) )
			$criteria['FROM']		= $this->sender;
		return $criteria;
	}

	public function renderCriteria(): string
	{
		$criteria	= [];
		if( NULL !== $this->subject && 0 !== strlen( trim( $this->subject ) ) )
			$criteria[]	= 'SUBJECT "'.$this->subject.'"';
		if( NULL !== $this->sender && 0 !== strlen( trim( $this->sender ) ) )
			$criteria[]	= 'FROM "'.$this->sender.'"';
		return join( ' ', $criteria );
	}

	/**
	 *	...
	 *	@access
	 *	@param		resource	$connection
	 *	@return		self
	 */
	public function setConnection( $connection ): self
	{
		$this->connection	= $connection;
		return $this;
	}

	public function setOrder( int $sort = SORTARRIVAL, bool $reverse = TRUE ): self
	{
		$this->setOrderSort( $sort );
		$this->setOrderReverse( $reverse );
		return $this;
	}

	public function setOrderSort( int $sort = SORTARRIVAL ): self
	{
		$this->orderSort		= $sort;
		return $this;
	}

	public function setOrderReverse( bool $reverse = TRUE ): self
	{
		$this->orderReverse		= $reverse;
		return $this;
	}

	public function setOffset( int $offset = 0 ): self
	{
		$this->offset	= $offset;
		return $this;
	}

	public function setLimit( int $limit = 10 ): self
	{
		$this->limit	= $limit;
		return $this;
	}

	public function setSubject( string $subject ): self
	{
		$this->subject	= trim( $subject );
		return $this;
	}

	public function setSender( string $sender ): self
	{
		$this->sender	= trim( $sender );
		return $this;
	}
}

/*
ALL CRITERIA:
	ALL - return all messages matching the rest of the criteria
	ANSWERED - match messages with the \\ANSWERED flag set
	BCC "string" - match messages with "string" in the Bcc: field
	BEFORE "date" - match messages with Date: before "date"
	BODY "string" - match messages with "string" in the body of the message
	CC "string" - match messages with "string" in the Cc: field
	DELETED - match deleted messages
	FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
	FROM "string" - match messages with "string" in the From: field
	KEYWORD "string" - match messages with "string" as a keyword
	NEW - match new messages
	OLD - match old messages
	ON "date" - match messages with Date: matching "date"
	RECENT - match messages with the \\RECENT flag set
	SEEN - match messages that have been read (the \\SEEN flag is set)
	SINCE "date" - match messages with Date: after "date"
	SUBJECT "string" - match messages with "string" in the Subject:
	TEXT "string" - match messages with text "string"
	TO "string" - match messages with "string" in the To:
	UNANSWERED - match messages that have not been answered
	UNDELETED - match messages that are not deleted
	UNFLAGGED - match messages that are not flagged
	UNKEYWORD "string" - match messages that do not have the keyword "string"
	UNSEEN - match messages which have not been read yet

DIVIDED INTO FLAGS AND CRITERIA:
	FLAGS:
	- ALL			- return all messages matching the rest of the criteria
	- ANSWERED		- match messages with the \\ANSWERED flag set
	- DELETED		- match deleted messages
	- FLAGGED		- match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
	- NEW			- match new messages
	- OLD			- match old messages
	- RECENT		- match messages with the \\RECENT flag set
	- SEEN			- match messages that have been read (the \\SEEN flag is set)
	- UNANSWERED	- match messages that have not been answered
	- UNDELETED		- match messages that are not deleted
	- UNFLAGGED		- match messages that are not flagged
	- UNSEEN		- match messages which have not been read yet

	CRITERIA:
	- BCC "string"			- match messages with "string" in the Bcc: field
	- BEFORE "date			- match messages with Date: before "date"
	- BODY "string"			- match messages with "string" in the body of the message
	- CC "string"			- match messages with "string" in the Cc: field
	- FROM "string"			- match messages with "string" in the From: field
	- KEYWORD "string"		- match messages with "string" as a keyword
	- ON "date"				- match messages with Date: matching "date"
	- SINCE "date"			- match messages with Date: after "date"
	- SUBJECT "string"		- match messages with "string" in the Subject:
	- TEXT "string"			- match messages with text "string"
	- TO "string"			- match messages with "string" in the To:
	- UNKEYWORD "string"	- match messages that do not have the keyword "string"


DIVIDED EVEN MORE:
	XOR FLAGS:
	- ANSWERED|UNANSWERED		- match messages that have (not) been answered
	- DELETED|UNDELETED			- match messages that are (not) deleted
	- FLAGGED|UNFLAGGED			- match messages that are (not) flagged
	- NEW|OLD					- match (not) new messages
	- SEEN|UNSEEN				- match messages which have (not) been read yet

	OTHER FLAGS:
	- ALL			- return all messages matching the rest of the criteria
	- RECENT		- match messages with the \\RECENT flag set

	CRITERIA: STRING BASED:
	- BCC "string"			- match messages with "string" in the Bcc: field
	- BODY "string"			- match messages with "string" in the body of the message
	- CC "string"			- match messages with "string" in the Cc: field
	- FROM "string"			- match messages with "string" in the From: field
	- KEYWORD "string"		- match messages with "string" as a keyword
	- SUBJECT "string"		- match messages with "string" in the Subject:
	- TEXT "string"			- match messages with text "string"
	- TO "string"			- match messages with "string" in the To:
	- UNKEYWORD "string"	- match messages that do not have the keyword "string"

	CRITERIA: DATE BASED:
	- BEFORE "date			- match messages with Date: before "date"
	- ON "date"				- match messages with Date: matching "date"
	- SINCE "date"			- match messages with Date: after "date"

*/
