<?php
declare(strict_types=1);

/**
 *	Parser for mail headers.
 *
 *	Copyright (c) 2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Mail\Address;
use DateTimeImmutable;
use DateTimeInterface;

use function call_user_func;

class Received
{
	/** @var		string|NULL					$from */
	protected ?string $from						= NULL;

	/** @var		string|NULL					$by */
	protected ?string $by						= NULL;

	/** @var		string|NULL					$with */
	protected ?string $with						= NULL;

	/** @var		string|NULL					$id */
	protected ?string $id						= NULL;

	/** @var		string|NULL					$via */
	protected ?string $via						= NULL;

	/** @var		Address|NULL				$for */
	protected ?Address $for						= NULL;

	/** @var		DateTimeInterface|NULL		$date */
	protected ?DateTimeInterface $date			= NULL;

	public function getFrom(): ?string
	{
		return $this->from;
	}

	public function getBy(): ?string
	{
		return $this->by;
	}

	public function getWith(): ?string
	{
		return $this->with;
	}

	public function getId(): ?string
	{
		return $this->id;
	}

	public function getVia(): ?string
	{
		return $this->via;
	}

	public function getFor(): ?Address
	{
		return $this->for;
	}

	public function getDate(): ?DateTimeInterface
	{
		return $this->date;
	}

	public static function parse( string $value ): self
	{
		$object	= new self();
		$parts	= explode( '; ', $value );
		if( 2 === count( $parts ) ){
			/** @noinspection PhpUnhandledExceptionInspection */
			$datetime	= new DateTimeImmutable( array_pop( $parts ) );
			$object->setDate( $datetime );
			$value	= join( '; ', $parts );
		}
		$value	= self::maskWordGroups( $value, '&' );

		$words	= explode( ' ', $value );
		$focus	= NULL;
		$buffer	= [];
		foreach( $words as $word ){
			$keyword	= strtolower( $word );
			if( in_array( $keyword, ['from', 'by', 'with', 'id', 'via', 'for'], TRUE ) ){
				if( NULL !== $focus ){
					$content	= str_replace( '&', ' ', join( ' ', $buffer ) );
					self::storeRetrievedData( $object, $focus, $content );
				}
				$focus	= $keyword;
				$buffer	= [];
			}
			else
				$buffer[]	= $word;
		}
		if( NULL !== $focus && 0 !== count( $buffer ) ){
			$content	= str_replace( '&', ' ', join( ' ', $buffer ) );
			self::storeRetrievedData( $object, $focus, $content );
		}
		return $object;
	}

	public function setFrom( string $from ): self
	{
		$this->from	= $from;
		return $this;
	}

	public function setBy( string $by ): self
	{
		$this->by	= $by;
		return $this;
	}

	public function setWith( string $with ): self
	{
		$this->with	= $with;
		return $this;
	}

	public function setId( string $id ): self
	{
		$this->id	= $id;
		return $this;
	}

	public function setVia( string $via ): self
	{
		$this->via	= $via;
		return $this;
	}

	public function setFor( Address $for ): self
	{
		$this->for	= $for;
		return $this;
	}

	public function setDate( DateTimeInterface $date ): self
	{
		$this->date	= $date;
		return $this;
	}

	/**
	 *	@access		public
	 *	@return		array<string,string|Address|DateTimeInterface|NULL>
	 */
	public function toArray(): array
	{
		return [
			'from'	=> $this->from,
			'by'	=> $this->by,
			'with'	=> $this->with,
			'id'	=> $this->id,
			'via'	=> $this->via,
			'for'	=> $this->for,
			'date'	=> $this->date,
		];
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		string		$string
	 *	@param		string		$mask
	 *	@return		string
	 */
	protected static function maskWordGroups( string $string, string $mask = '–' ): string
	{
		$status = 0;
		for( $i=0; $i<strlen( $string ); $i++ ){
			$char = $string[$i];
			if( 0 === $status ){
				if( in_array( $char, ['(', '[', '<'], TRUE ) )
					$status = 1;

			}
			else{
				if( in_array( $char, [')', ']', '>'], TRUE ) )
					$status = 0;
				else if( ' ' === $char )
					$string[$i]	= $mask;
			}
		}
		return $string;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@static
	 *	@param		self		$object
	 *	@param		string		$key
	 *	@param		string		$value
	 *	@return		void
	 */
	protected static function storeRetrievedData( self $object, string $key, string $value )
	{
		$method		= 'set'.ucfirst( $key );
		$callable	= [$object, $method];
		if( 'for' === $key ){
			$value	= preg_replace( '/ \(.+\)/', '', $value );
			$value	= new Address( $value );
		}
		if(is_callable($callable))
			call_user_func( $callable, $value );
	}
}
