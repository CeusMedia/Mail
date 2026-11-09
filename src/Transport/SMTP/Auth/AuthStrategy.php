<?php
declare(strict_types=1);

/**
 *	Strategy interface for SMTP AUTH methods.
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
namespace CeusMedia\Mail\Transport\SMTP\Auth;

use CeusMedia\Mail\Transport\SMTP\SmtpConnection;

/**
 *	Strategy interface for SMTP AUTH methods.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
interface AuthStrategy
{
	/**
	 *	Returns the AUTH mechanism name, e.g. "LOGIN", "PLAIN", "XOAUTH2".
	 *	@access		public
	 *	@return		string
	 */
	public function getMechanism(): string;

	/**
	 *	Perform the AUTH exchange with the SMTP server.
	 *	The connection object provides sendChunk() and readResponse() methods.
	 *	@access		public
	 *	@param		SmtpConnection	$connection	SMTP connection for sending commands and reading responses
	 *	@throws		\RuntimeException	if authentication fails
	 */
	public function authenticate( SmtpConnection $connection ): void;
}

