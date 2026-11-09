<?php
declare(strict_types=1);

/**
 *	SMTP AUTH PLAIN strategy (RFC 4616).
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
 *	SMTP AUTH PLAIN strategy (RFC 4616).
 *	Sends authorization identity, authentication identity, and password in a single base64-encoded chunk.
 *	Format: base64( authz_id \0 auth_id \0 password )
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Plain implements AuthStrategy
{
	/** @var string  $authzId  Authorization identity (optional, may differ from auth id) */
	protected string $authzId;

	/** @var string  $authId   Authentication identity (username) */
	protected string $authId;

	/** @var string  $password */
	protected string $password;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$authId		Authentication identity (username)
	 *	@param		string		$password	SMTP password
	 *	@param		string		$authzId		Authorization identity (optional, defaults to authId)
	 */
	public function __construct( string $authId, string $password, string $authzId = '' )
	{
		$this->authId		= $authId;
		$this->password	= $password;
		$this->authzId	= '' !== $authzId ? $authzId : $authId;
	}

	public function getMechanism(): string
	{
		return 'PLAIN';
	}

	public function authenticate( SmtpConnection $connection ): void
	{
		//  RFC 4616: base64( authz_id \0 auth_id \0 password )
		$credentials	= $this->authzId."\0".$this->authId."\0".$this->password;

		$connection->sendChunk( 'AUTH PLAIN '.$credentials );
		$response	= $connection->readResponse();

		if( 235 !== $response->getCode() ){
			throw new RuntimeException(
				'AUTH PLAIN failed: expected 235, got '.$response->getCode().' '.$response->getMessage()
			);
		}
	}
}

