<?php
declare(strict_types=1);

/**
 *	...
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
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
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
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Response
{
	public const ERROR_NONE						= 0;
	public const ERROR_UNSPECIFIED				= 1;
	public const ERROR_NO_HOST_SET				= 2;
	public const ERROR_NO_PORT_SET				= 3;
	public const ERROR_MX_RESOLUTION_FAILED		= 4;
	public const ERROR_SOCKET_FAILED			= 5;
	public const ERROR_SOCKET_EXCEPTION			= 6;
	public const ERROR_CONNECTION_FAILED		= 7;
	public const ERROR_RESPONSE_NOT_UNDERSTOOD	= 8;
	public const ERROR_CODE_NOT_ACCEPTED		= 9;
	public const ERROR_HELO_FAILED				= 10;
	public const ERROR_CRYPTO_FAILED			= 11;
	public const ERROR_LOGIN_FAILED				= 12;
	public const ERROR_SENDER_NOT_ACCEPTED		= 13;
	public const ERROR_RECEIVER_NOT_ACCEPTED	= 14;

	/** @var	int[]		$acceptedCodes */
	public array $acceptedCodes	= [];

	/** @var	integer			$code */
	public int $code			= 0;

	/** @var	string			$message */
	public string $message		= '';

	/** @var	int				$error */
	public int $error			= self::ERROR_NONE;

	/** @var	string			$request */
	public string $request		= '';

	/** @var	string[]		$response */
	public array $response		= [];

	public function __construct( ?int $code = NULL, ?string $message = NULL )
	{
		if( NULL !== $code )
			$this->code = $code;
		if( NULL !== $message )
			$this->message = $message;
	}

	public function getAcceptedCodes(): array
	{
		return $this->acceptedCodes;
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function getError(): int
	{
		return $this->error;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getRequest(): string
	{
		return $this->request;
	}

	public function getResponse(): array
	{
		return $this->response;
	}

	public function isAccepted(): ?bool
	{
		if( self::ERROR_NONE !== $this->error )
			return FALSE;
		if( 0 === count( $this->acceptedCodes ) )
			return NULL;
		return in_array( $this->code, $this->acceptedCodes, TRUE );
	}

	public function isError(): bool
	{
		return self::ERROR_NONE !== $this->error;
	}

	public function setAcceptedCodes( array $codes ): self
	{
		$this->acceptedCodes	= $codes;
		return $this;
	}

	public function setCode( int $code ): self
	{
		$this->code		= $code;
		return $this;
	}

	public function setError( int $error ): self
	{
		$this->error	= $error;
		return $this;
	}

	public function setMessage( string $message = '' ): self
	{
		$this->message	= $message;
		return $this;
	}

	public function setRequest( string $request = '' ): self
	{
		$this->request	= $request;
		return $this;
	}

	public function setResponse( array $response = [] ): self
	{
		$this->response	= $response;
		return $this;
	}
}
