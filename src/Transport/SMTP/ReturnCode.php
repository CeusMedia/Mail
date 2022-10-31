<?php
/** @noinspection PhpUnused */
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
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport\SMTP;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class ReturnCode
{
	public const SYSTEM_STATUS				= 211;		// System status, or system help reply
	public const HELP_MESSAGE				= 214;		// Help message (Information on how to use the receiver or the meaning of a particular non-standard command; this reply is useful only to the human user)
	public const SERVICE_READY				= 220;		// <domain> Service ready
	public const DISCONNECTING				= 221;		// <domain> Service closing transmission channel
	public const AUTH_OK					= 235;		// Authentication Succeeded
	public const OK							= 250;		// Requested mail action okay, completed
	public const FORWARD					= 251;		// User not local; will forward to <forward-path> (See Section 3.4)
	public const CANNOT_VERIFY_USER			= 252;		//  Cannot verify user, but will accept message and attempt delivery
	public const AUTH_CONTINUE				= 334;
	public const START_MAIL					= 354;		// Start mail input; end with <CRLF>.<CRLF>
	public const SERVICE_NOT_AVAILABLE		= 421;		// <domain> Service not available, closing transmission channel (This may be a reply to any command if the service knows it must shut down)
	public const PASSWORD_TRANSITION_NEEDED	= 432;
	public const MAILBOX_TEMP_UNAVAILABLE	= 450;		// Requested mail action not taken: mailbox unavailable (e.g., mailbox busy or temporarily blocked for policy reasons)
	public const LOCAL_ERROR				= 451;		// Requested action aborted: local error in processing
	public const SYSTEM_STORAGE_ERROR		= 452;		// Requested action not taken: insufficient system storage
	public const TEMP_AUTH_FAILURE			= 454;		// Temporary authentication failure
	public const PARAMETERS_ERROR			= 455;		// Server unable to accommodate parameters
	public const SYNTAX_ERROR				= 500;		// Syntax error, command unrecognized (This may include errors such as command line too long)
	public const ARGUMENTS_SYNTAX_ERRORS	= 501;		// Syntax error in parameters or arguments
	public const COMMAND_NOT_IMPLEMENTED	= 502;		// Command not implemented
	public const BAD_COMMANDS_SEQUENCE		= 503;		// Bad sequence of commands
	public const COMMAND_PARAMETER_ERROR	= 504;		// Command parameter not implemented
	public const AUTH_REQUIRED				= 530;		// Authentication required
	public const WEEK_AUTH_METHOD			= 534;		// Authentication mechanism is too weak
	public const INVALID_CREDENTIALS		= 535;		// Authentication credentials invalid
	public const ENCRYPTION_REQUIRED		= 538;		// Encryption required for requested authentication mechanism
	public const MAILBOX_UNAVAILABLE		= 550;		// Requested action not taken: mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons)
	public const NON_LOCAL_USER				= 551;		// User not local; please try <forward-path>
	public const STORAGE_LIMIT_EXCEEDED		= 552;		// Requested mail action aborted: exceeded storage allocation
	public const MAILBOX_NAME_ERROR			= 553;		// Requested action not taken: mailbox name not allowed (e.g., mailbox syntax incorrect)
	public const TRANSACTION_FAILED			= 554;		// Transaction failed (Or, in the case of a connection-opening response, "No SMTP service here")
	public const RECIPIENT_OR_SENDER_ERROR	= 555;		// MAIL FROM/RCPT TO parameters not recognized or not implemented
}
