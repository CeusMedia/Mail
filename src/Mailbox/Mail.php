<?php
declare(strict_types=1);

/**
 *	Mailbox Mail Item Container.
 *
 *	Copyright (c) 2007-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
namespace CeusMedia\Mail\Mailbox;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser as MessageParser;
use CeusMedia\Mail\Message\Header\Parser as MessageHeaderParser;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

use IMAP\Connection as ImapConnection;
use ReflectionException;
use RuntimeException;

use function imap_body;
use function imap_fetchheader;

/**
 *	Mailbox Mail Item Container.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 */
class Mail
{
	/**	@var	Connection|NULL				$connection */
	protected ?Connection $connection;

	/**	@var	ImapConnection|NULL				$rawConnection */
	protected ?ImapConnection $rawConnection;

	/**	@var	integer							$mailId */
	protected int $mailId;

	/**	@var	string|NULL						$rawHeader */
	protected ?string $rawHeader				= NULL;

	/**	@var	MessageHeaderSection|NULL		$header */
	protected ?MessageHeaderSection $header		= NULL;

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
		if( NULL === $this->header || $force ){
			$this->header	= MessageHeaderParser::getInstance()->parse(
				$this->getRawHeader( $force )
			);
		}
		return $this->header;
	}

	/**
	 *	...
	 *	@public
	 *	@param		boolean		$withBodyParts
	 *	@return		Message
	 *	@throws		ReflectionException
	 */
	public function getMessage( bool $withBodyParts = FALSE ): Message
	{
		if( NULL === $this->rawConnection )
			throw new RuntimeException( 'No connection set' );
		$rawHeader	= $this->getRawHeader();
		$rawBody	= '';
		if( $withBodyParts )
			$rawBody	= imap_body( $this->rawConnection, $this->mailId, FT_UID | FT_PEEK );
		return MessageParser::getInstance()->parse( $rawHeader.PHP_EOL.PHP_EOL.$rawBody );
	}

	/**
	 *	Get mail header as raw string.
	 *	@access		public
	 *	@param		boolean			$force			Flag: force reading of raw header if already fetched
	 *	@return		string			Raw mail header as string
	 */
	public function getRawHeader( bool $force = FALSE ): string
	{
		if( NULL === $this->rawConnection )
			throw new RuntimeException( 'No connection set' );
		if( NULL === $this->rawHeader || '' === trim( $this->rawHeader ) || $force ){
			$rawHeader	= imap_fetchheader( $this->rawConnection, $this->mailId, FT_UID );
			if( FALSE === $rawHeader )
				throw new RuntimeException( 'Invalid mail ID or fetching mail header failed' );
			$this->rawHeader	= $rawHeader;
		}
		return $this->rawHeader;
	}

	/**
	 *	Set mailbox connection.
	 *	@access		public
	 *	@param		Connection		$connection
	 *	@return		self
	 */
	public function setConnection( Connection $connection ): self
	{
		$this->connection		= $connection;
		$this->rawConnection	= $connection->getResource( TRUE );
		return $this;
	}
}
