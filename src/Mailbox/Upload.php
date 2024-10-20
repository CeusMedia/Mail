<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

/**
 *    Stores raw mail message as mail in mailbox folder.
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
 */
namespace CeusMedia\Mail\Mailbox;

use A\B;
use CeusMedia\Common\ADT\Bitmask;
use CeusMedia\Mail\Message;
use RangeException;
use RuntimeException;

/**
 *	Stores raw mail message as mail in mailbox folder.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Mailbox
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Upload
{
	const FLAG_SEEN		= 1;
	const FLAG_ANSWERED	= 2;
	const FLAG_FLAGGED	= 4;

	const FLAGS			= [
		self::FLAG_SEEN,
		self::FLAG_ANSWERED,
		self::FLAG_FLAGGED,
	];

	protected Bitmask $basicFlags;
	protected Connection $connection;
	protected string $folder;
	protected ?string $error	= NULL;

	/**
	 *	Constructor.
	 *	@param		Connection		$connection
	 *	@param		string			$folder
	 *	@param		Bitmask|int		$basicFlags
	 */
	public function __construct( Connection $connection, string $folder, Bitmask|int $basicFlags = 0 )
	{
		$this->connection	= $connection;
		$this->setFolder( $folder );
		$this->setBasicFlags( $basicFlags );
	}

	/**
	 *	Set bit flags to apply to each upload by default.
	 *	Can be extended with additional flags on each upload.
	 *	@param		Bitmask|int $flags
	 *	@return		self
	 */
	public function setBasicFlags( Bitmask|int $flags ): self
	{
		$flags	= is_int( $flags ) ? new Bitmask( $flags ) : $flags;
		if( $flags->without( Bitmask::fromArray( self::FLAGS ) )->get() > 0 )
			throw new RangeException( 'Invalid flag' );

		$this->basicFlags	= $flags;
		return $this;
	}

	/**
	 *	Sets folder to store messages in.
	 *	@param		string		$folder
	 *	@return		self
	 */
	public function setFolder( string $folder ): self
	{
		$this->folder	= $folder;
		return $this;
	}

	/**
	 *	Stores a mail onto set mailbox folder.
	 *	Given additional flags (if given) will extend set basic flags (if set).
	 *	Returns boolean result, handling errors depending on strict mode.
	 *	- Will lead to exceptions or errors if strict mode is on.
	 *	- Otherwise, will store errors internally.
	 *
	 *	@param		Message			$message
	 *	@param		Bitmask|int		$additionalFlags
	 *	@param		bool			$strict				Flag: strict mode (default: yes)
	 *	@return		bool
	 *	@throws		RuntimeException	if upload failed with strict mode enabled
	 */
	public function storeMessage( Message $message, Bitmask|int $additionalFlags = 0, bool $strict = TRUE ): bool
	{
		$additionalFlagsInt	= !is_int( $additionalFlags ) ? $additionalFlags->get() : $additionalFlags;
		$combinedFlags		= ( clone $this->basicFlags )->add( $additionalFlagsInt );

		$reference	= $this->connection->renderReference( FALSE );
		$folder		= mb_convert_encoding( $this->folder, 'UTF-8', 'UTF7-IMAP' );
		$result		= @imap_append(
			$this->connection->getResource( TRUE ),
			$reference.$folder,
			Message\Renderer::render( $message ),
			$this->renderFlags( $combinedFlags )
		);
		if( FALSE !== imap_last_error() ){
			$this->error	= imap_last_error();
			if( $strict )
				throw new RuntimeException( 'Storing message failed: '.$this->error );
		}
		return $result;
	}

	/**
	 *	Realizes flags as chain of strings, like '\Seen\Answered'
	 *	@param		Bitmask		$flags
	 *	@return		string
	 */
	protected function renderFlags( Bitmask $flags ): string
	{
		$options	= [];
		if( $flags->has( Upload::FLAG_SEEN ) )
			$options[]	= "\\Seen";
		if( $flags->has( Upload::FLAG_ANSWERED ) )
			$options[]	= "\\Answered";
		if( $flags->has( Upload::FLAG_FLAGGED ) )
			$options[]	= "\\Flagged";
		return join( $options );
	}
}