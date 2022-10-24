<?php
declare(strict_types=1);

/**
 *	Parser for list of addresses collected as string.
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
 *	@package		CeusMedia_Mail_Address_Collection
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Collection;

use CeusMedia\Common\Alg\Obj\Constant as ObjectConstant;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;
use CeusMedia\Mail\Deprecation;

use InvalidArgumentException;
use RangeException;

use function array_shift;
use function extension_loaded;
use function imap_rfc822_parse_adrlist;
use function in_array;
use function strlen;
use function str_replace;
use function str_split;
use function trim;

/**
 *	Parser for list of addresses collected as string.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Collection
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			Finish code documentation
 */
class Parser
{
	public const METHOD_AUTO					= 0;
	public const METHOD_IMAP					= 1;
	public const METHOD_OWN						= 2;
	public const METHOD_IMAP_PLUS_OWN			= 3;

	public const METHODS						= [
		self::METHOD_AUTO,
		self::METHOD_IMAP,
		self::METHOD_OWN,
		self::METHOD_IMAP_PLUS_OWN,
	];

	protected const STATE_SCANNING_FOR_NAME		= 0;
	protected const STATE_READING_NAME			= 1;
	protected const STATE_READING_QUOTED_NAME	= 2;
	protected const STATE_SCANNING_FOR_ADDRESS	= 3;
	protected const STATE_READING_ADDRESS		= 4;

	/** @var int $method */
	protected $method							= 0;

	/**
	 *	Static constructor.
	 *	@access			public
	 *	@static
	 *	@return			self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 *	@codeCoverageIgnore
	 */
	public static function create(): self
	{
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message( 'Use method getInstance instead' );
		return new self();
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 *	Get set parser method.
	 *	@access		public
	 *	@return		integer
	 */
	public function getMethod(): int
	{
		return $this->method;
	}

	/**
	 *	Parse address collection string and return address collection.
	 *	@access		public
	 *	@param		string		$string		Address collection string
	 *	@param		string		$delimiter	Delimiter (default: ,)
	 *	@return		AddressCollection
	 *	@throws		RangeException			if parser method is not supported
	 */
	public function parse( string $string, string $delimiter = "," ): AddressCollection
	{
		$method		= $this->realizeParserMethod();
		switch( $method ){
			case static::METHOD_IMAP_PLUS_OWN:
				$collection	= $this->parseUsingImap( $string );										//  get collection using IMAP functions
				$string		= AddressCollectionRenderer::getInstance()->render( $collection );		//  render collection string
				return $this->parseUsingOwn( $string );												//  get collection using own implementation
			case static::METHOD_IMAP:
				return $this->parseUsingImap( $string );											//  get collection using IMAP functions
			case static::METHOD_OWN;
				return $this->parseUsingOwn( $string );												//  get collection using own implementation
			default:
				throw new RangeException( 'No supported parser set' );
		}
	}

	public function parseUsingImap( string $string ): AddressCollection
	{
		$string			= trim( $string, "\t\r\n, " );
		$collection		= new AddressCollection();
		$list			= imap_rfc822_parse_adrlist( $string, '_invalid.tld' );
		foreach( $list as $item ){
			$address	= new Address();
			$address->setLocalPart( $item->mailbox );
			$address->setDomain( $item->host );
			if( isset( $item->personal ) && strlen( trim( $item->personal ) ) > 0 )
				$address->setName( $item->personal );
			$collection->add( $address );
		}
		return $collection;
	}

	public function parseUsingOwn( string $string, string $delimiter = ',' ): AddressCollection
	{
		if( 0 === strlen( trim( $delimiter ) ) )
			throw new InvalidArgumentException( 'Delimiter cannot be empty or whitespace' );
		$list		= [];
		$string		= str_replace( "\r", "", str_replace( "\n", "", $string ) );
		if( 0 === strlen( trim( $string ) ) )
			return new AddressCollection();
		$status		= static::STATE_SCANNING_FOR_NAME;
		$part1		= "";
		$buffer		= "";
		$letters	= str_split( trim( $string ), 1 );
		while( count( $letters ) ){
			$letter = array_shift( $letters );
			if( $status === static::STATE_SCANNING_FOR_NAME  ){
				if( $letter === '"' ){
					$status = static::STATE_READING_QUOTED_NAME;
					continue;
				}
				if( $letter === " " || $letter === $delimiter )
					continue;
				if( $letter === '<' ){
					$status	= static::STATE_READING_ADDRESS;
					continue;
				}
				$status = static::STATE_READING_NAME;
			}
			else if( $status === static::STATE_READING_NAME ){
				if( $letter === '<' ){
					$part1	= trim( $buffer );
					$buffer	= "";
					$status = static::STATE_READING_ADDRESS;
					continue;
				}
				if( $letter === '@' ){
					$status	= static::STATE_READING_ADDRESS;
				}
			}
			else if( $status === static::STATE_READING_QUOTED_NAME ){
				if( $letter === '"' ){
					$status = static::STATE_SCANNING_FOR_ADDRESS;
					$part1	= $buffer;
					$buffer	= "";
					continue;
				}
			}
			else if( $status === static::STATE_SCANNING_FOR_ADDRESS ){
				if( $letter === " " || $letter === "<" )
					continue;
				$status	= static::STATE_READING_ADDRESS;
			}
			else if( $status === static::STATE_READING_ADDRESS ){
				if( $letter === '>' || $letter === " " || $letter === $delimiter ){
					$list[]	= [ 'fullname' => $part1, 'address' => trim( $buffer ) ];
					$part1	= "";
					$buffer	= "";
					$status	= static::STATE_SCANNING_FOR_NAME;
					continue;
				}
			}
			$buffer	.= $letter;
		}
		if( 0 < strlen( $buffer ) && 0 < $status )
			$list[]	= [ 'fullname' => $part1, 'address' => trim( $buffer ) ];

		$collection	= new AddressCollection();
		foreach( $list as $entry ){
			$address		= trim( $entry['fullname'].' <'.$entry['address'].'>' );
			$collection->add( new Address( $address ) );
		}
		return $collection;
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		integer		$method		Parser method to set, see METHOD_* constants
	 *	@return		self
	 *	@throws		InvalidArgumentException	if given method is unknown
	 */
	public function setMethod( int $method ): self
	{
		$reflection	= new ObjectConstant( get_class( $this ) );
		/** @var array $constants */
		$constants	= $reflection->getAll( 'METHOD' );
		if( !in_array( $method, $constants, TRUE ) )
			throw new InvalidArgumentException( 'Invalid method' );
		$this->method	= $method;
		return $this;
	}

	/*  --  PROTECTED  --  */

	protected function realizeParserMethod(): int
	{
		$method		= $this->method;
//		$hasImap	= function_exists( 'imap_rfc822_parse_adrlist' );
		$hasImap	= extension_loaded( 'imap' );
		/*  downgrade  */
		if( $method === static::METHOD_IMAP_PLUS_OWN && !$hasImap )
			$method = static::METHOD_OWN;
		if( $method === static::METHOD_IMAP && !$hasImap )
			$method = static::METHOD_AUTO;
		/*  upgrade  */
		if( $method === static::METHOD_AUTO && $hasImap )
			$method = static::METHOD_IMAP;
		if( $method === static::METHOD_AUTO )
			$method = static::METHOD_OWN;
		return $method;
	}
}
