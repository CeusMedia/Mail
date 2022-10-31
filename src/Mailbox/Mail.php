<?php
declare(strict_types=1);

/**
 *	...
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
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
namespace CeusMedia\Mail\Mailbox;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser as MessageParser;
use CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

use RuntimeException;

use function imap_body;
use function imap_fetchheader;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
class Mail
{
	/**	@var	resource		$connection */
	protected $connection;

	/**	@var	integer			$mailId */
	protected int $mailId;

	/**	@var	string|NULL		$header */
	protected ?string $header	= NULL;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		integer		$mailId			Mail ID as integer
	 */
	public function __construct( int $mailId )
	{
		$this->mailId	= $mailId;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@param		integer		$mailId
	 *	@return		self
	 */
	public static function getInstance( int $mailId ): self
	{
		return new self( $mailId );
	}

	/**
	 *	Get mail ID of this mail instance.
	 *	@access		public
	 *	@return		integer			Mail ID of this mail instance
	 */
	public function getId(): int
	{
		return $this->mailId;
	}

	/**
	 *	Get mail header as message header section instance.
	 *	@access		public
	 *	@param		boolean			$force			Flag: force reading of raw header if already fetched
	 *	@return		MessageHeaderSection			Mail ID of this mail instance
	 */
	public function getHeader( bool $force = FALSE ): MessageHeaderSection
	{
		$this->getRawHeader( $force );
		/** @phpstan-ignore-next-line */
		return MessageHeaderParser::getInstance()->parse( $this->header );
	}

	/**
	 *	...
	 *	@public
	 *	@param		boolean		$withBodyParts
	 *	@return		Message
	 */
	public function getMessage( bool $withBodyParts = FALSE ): Message
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'No connection set' );
		$header	= $this->getRawHeader();
		$body	= '';
		if( $withBodyParts )
			$body	= imap_body( $this->connection, $this->mailId, FT_UID | FT_PEEK );
		return MessageParser::getInstance()->parse( $header.PHP_EOL.PHP_EOL.$body );
	}

	/**
	 *	Get mail header as raw string.
	 *	@access		public
	 *	@param		boolean			$force			Flag: force reading of raw header if already fetched
	 *	@return		string			Raw mail header as string
	 */
	public function getRawHeader( bool $force = FALSE ): string
	{
		if( NULL === $this->connection )
			throw new RuntimeException( 'No connection set' );
		if( NULL === $this->header || 0 === strlen( $this->header ) || $force ){
			$header	= imap_fetchheader( $this->connection, $this->mailId, FT_UID );
			if( FALSE === $header )
				throw new RuntimeException( 'Invalid mail ID' );
			$this->header	= $header;
		}
		return $this->header;
	}

	/**
	 *	Set mailbox connection.
	 *	@access		public
	 *	@param		resource		$connection
	 *	@return		self
	 */
	public function setConnection( $connection ): self
	{
		$this->connection	= $connection;
		return $this;
	}
}
