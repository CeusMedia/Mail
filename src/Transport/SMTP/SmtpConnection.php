<?php
declare(strict_types=1);

/**
 *	Interface for SMTP connection operations used by AUTH strategies.
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
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport\SMTP;

/**
 *	Interface for SMTP connection operations used by AUTH strategies.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
interface SmtpConnection
{
	/**
	 *	Send a command chunk to the SMTP server.
	 *	@access		public
	 *	@param		string		$command			SMTP command to send (already includes CRLF)
	 *	@param		string|null	$verboseMessage	Optional replacement text for verbose logging (e.g. to mask credentials)
	 *	@return		bool
	 */
	public function sendChunk( string $command, ?string $verboseMessage = NULL ): bool;

	/**
	 *	Read the next SMTP response from the server.
	 *	@access		public
	 *	@return		Response
	 */
	public function readResponse(): Response;
}


