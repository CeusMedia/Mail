<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Entity for transport results.
 *	Each result is assigned to one receiving address.
 *
 *	Copyright (c) 2024-2024 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2024-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */

namespace CeusMedia\Mail\Transport;

use CeusMedia\Mail\Address;
use RangeException;

/**
 *	Entity for transport results.
 *	Each result is assigned to one receiving address.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2024-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Result
{
	const STATUS_UNKNOWN		= 'unknown';
	const STATUS_SUCCESS		= 'ok';
	const STATUS_FAILURE		= 'failed';
	const STATUS_ERROR			= 'error';

	const STATUSES				= [
		self::STATUS_UNKNOWN,
		self::STATUS_SUCCESS,
		self::STATUS_FAILURE,
		self::STATUS_ERROR,
	];

	public Address|string $receiver;
	public string $status			= self::STATUS_UNKNOWN;
	public int $code				= 0;
	public string|NULL $message;
	public int|string|NULL $id;

	/**
	 *	Get result code.
	 *	@return		int
	 */
	public function getCode(): int
	{
		return $this->code;
	}

	/**
	 *	Get result id.
	 *	@return		int|string|NULL
	 */
	public function getId(): int|string|NULL
	{
		return $this->id;
	}

	/**
	 *	Get result message, if set.
	 *	@return		string|NULL
	 */
	public function getMessage(): string|NULL
	{
		return $this->message;
	}

	/**
	 *	@return		Address|string
	 */
	public function getReceiver(): Address|string
	{
		return $this->receiver;
	}

	/**
	 *	Get result status. One of Result::STATUSES.
	 *	@return		string
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	/**
	 *	Set result code.
	 *	@param		int		$code
	 *	@return		self
	 */
	public function setCode( int $code ): self
	{
		$this->code		= $code;
		return $this;
	}

	/**
	 *	Set result ID.
	 *	@param		int|string|NULL		$id
	 *	@return		self
	 */
	public function setId( int|string|null $id ): self
	{
		$this->id		= $id;
		return $this;
	}

	/**
	 *	@param		string|NULL		$message
	 *	@return		self
	 */
	public function setMessage( ?string $message ): self
	{
		$this->message	= $message;
		return $this;
	}

	/**
	 *	@param		Address|string		$receiver
	 *	@return		self
	 */
	public function setReceiver( Address|string $receiver ): self
	{
		$this->receiver	= $receiver;
		return $this;
	}

	/**
	 *	Set result status. One of Result::STATUSES.
	 *	@param		string		$status
	 *	@return		self
	 */
	public function setStatus( string $status ): self
	{
		if( !in_array( $status, self::STATUSES, TRUE ) )
			throw new RangeException( 'Invalid status' );
		$this->status	= $status;
		return $this;
	}
}
