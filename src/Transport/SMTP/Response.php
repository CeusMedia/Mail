<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2007-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2021 Christian Würker
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
 *	@copyright		2007-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Response
{
	const ERROR_NONE					= 0;
	const ERROR_MX_RESOLUTION_FAILED	= 1;
	const ERROR_SOCKET_FAILED			= 2;
	const ERROR_SOCKET_EXCEPTION		= 3;
	const ERROR_CONNECTION_FAILED		= 4;
	const ERROR_HELO_FAILED				= 5;
	const ERROR_CRYPTO_FAILED			= 6;
	const ERROR_SENDER_NOT_ACCEPTED		= 7;
	const ERROR_RECEIVER_NOT_ACCEPTED	= 8;

	/** @var	integer		$code */
	public $code			= 0;

	/** @var	string		$message */
	public $message			= '';

	/** @var	int			$error */
	public $error			= self::ERROR_NONE;

	/** @var	string		$request */
	public $request			= '';

	/** @var	string		$response */
	public $response		= '';

	public function __construct( ?int $code = NULL, ?string $message = NULL )
	{
		if( NULL !== $code )
			$this->code = $code;
		if( NULL !== $message )
			$this->message = $message;
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

	public function getResponse(): string
	{
		return $this->response;
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

	public function setResponse( string $response = '' ): self
	{
		$this->response	= $response;
		return $this;
	}
}