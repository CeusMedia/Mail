<?php
declare(strict_types=1);

/**
 *	SMTP AUTH XOAUTH2 strategy (Google OAuth 2.0 for SMTP).
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

use function base64_encode;
use function sprintf;

/**
 *	SMTP AUTH XOAUTH2 strategy.
 *	Uses OAuth 2.0 access tokens for authentication (Gmail, Microsoft 365, etc.).
 *	SASL IR (Initial Response) is supported — sends the token in the first AUTH command.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://developers.google.com/gmail/imap/xoauth2-protocol
 */
class Xoauth2 implements AuthStrategy
{
	/** @var string  $username   OAuth client email / username */
	protected string $username;

	/** @var string  $accessToken  OAuth 2.0 access token */
	protected string $accessToken;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$username		OAuth client email or username
	 *	@param		string		$accessToken	OAuth 2.0 access token
	 */
	public function __construct( string $username, string $accessToken )
	{
		$this->username		= $username;
		$this->accessToken	= $accessToken;
	}

	public function getMechanism(): string
	{
		return 'XOAUTH2';
	}

	public function authenticate( SmtpConnection $connection ): void
	{
		//  SASL IR: send initial response with AUTH command
		//  Format: user=<username>\1auth=Bearer <token>\1\1
		$saslIR	= sprintf( 'user=%s\1auth=Bearer %s\1\1', $this->username, $this->accessToken );

		$connection->sendChunk( 'AUTH XOAUTH2 '.base64_encode( $saslIR ) );
		$response	= $connection->readResponse();

		if( 235 === $response->getCode() ){
			//  SASL IR succeeded — authentication complete
			return;
		}

		//  Server may not support SASL IR — fall back to challenge-response
		if( 334 === $response->getCode() ){
			//  Send empty response to trigger server challenge
			$connection->sendChunk( '' );
			$response	= $connection->readResponse();

			if( 334 === $response->getCode() ){
				//  Server sent a challenge — send our SASL IR as response
				$connection->sendChunk( base64_encode( $saslIR ) );
				$response	= $connection->readResponse();
			}
		}

		if( 235 !== $response->getCode() ){
			//  Collect additional error details from server response lines
			$details	= '';
			if( $response->getCode() === 535 || $response->getCode() === 534 ){
				$details	= ' '.$response->getMessage();
			}
			throw new RuntimeException(
				'AUTH XOAUTH2 failed: expected 235, got '.$response->getCode().$details
			);
		}
	}
}

