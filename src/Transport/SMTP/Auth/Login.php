<?php
declare(strict_types=1);

/**
 *	SMTP AUTH LOGIN strategy (RFC 4616 de facto, widely supported).
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
use RuntimeException;

/**
 *	SMTP AUTH LOGIN strategy.
 *	Server sends base64-encoded username prompt, then base64-encoded password prompt.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Login implements AuthStrategy
{
	/** @var string  $username */
	protected string $username;

	/** @var string  $password */
	protected string $password;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$username	SMTP username
	 *	@param		string		$password	SMTP password
	 */
	public function __construct( string $username, string $password )
	{
		$this->username	= $username;
		$this->password	= $password;
	}

	public function getMechanism(): string
	{
		return 'LOGIN';
	}

	public function authenticate( SmtpConnection $connection ): void
	{
		//  Initiate AUTH LOGIN
		$connection->sendChunk( 'AUTH LOGIN' );
		$response	= $connection->readResponse();
		if( 334 !== $response->getCode() ){
			throw new RuntimeException(
				'AUTH LOGIN failed: expected 334, got '.$response->getCode().' '.$response->getMessage()
			);
		}

		//  Send base64-encoded username (masked in verbose output)
		$connection->sendChunk( base64_encode( $this->username ), '[username hidden]' );
		$response	= $connection->readResponse();
		if( 334 !== $response->getCode() ){
			throw new RuntimeException(
				'AUTH LOGIN failed: expected 334 after username, got '.$response->getCode().' '.$response->getMessage()
			);
		}

		//  Send base64-encoded password (masked in verbose output)
		$connection->sendChunk( base64_encode( $this->password ), '[password hidden]' );
		$response	= $connection->readResponse();
		if( 235 !== $response->getCode() ){
			throw new RuntimeException(
				'AUTH LOGIN failed: expected 235, got '.$response->getCode().' '.$response->getMessage()
			);
		}
	}
}

