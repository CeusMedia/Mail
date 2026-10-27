<?php
declare(strict_types=1);

/**
 *	Renderer for list of addresses.
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
 *	@package		CeusMedia_Mail_Address_Collection
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address\Collection;

use CeusMedia\Mail\Address\Collection as AddressCollection;
use CeusMedia\Mail\Address\Renderer as AddressRenderer;
use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Util\Str;

use InvalidArgumentException;

use function join;
use function max;
use function rtrim;
use function str_contains;
use function strlen;
use function strpos;

/**
 *	Renderer for list of addresses, for headers with encoded names and folding.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address_Collection
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Renderer
{
	/** @var string $delimiter */
	protected string $delimiter		= ', ';

	/** @var bool $encodeNames		Flag: encode names (RFC 2047) and quote special characters, for use in headers */
	protected bool $encodeNames		= FALSE;

	/** @var int $lineLength		Maximum line length for folding between addresses, 0: no folding */
	protected int $lineLength		= 0;

	/** @var		integer		$offset			Length of text before the list in first line, e.g. 4 for "To: " */
	protected int $offset			= 0;

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
			->message(  'Use method getInstance instead' );
		return new self();
	}

	/**
	 *	Returns set delimiter.
	 *	@access		public
	 *	@return		string
	 */
	public function getDelimiter(): string
	{
		return $this->delimiter;
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
	 *	Returns maximum line length for folding, 0 if folding is disabled.
	 *	@access		public
	 *	@return		integer
	 */
	public function getLineLength(): int
	{
		return $this->lineLength;
	}

	/**
	 *	Returns length of text before the list in first line, e.g. 4 for "To: ".
	 *	@access		public
	 *	@return		integer
	 */
	public function getOffset(): int
	{
		return $this->offset;
	}

	/**
	 *	Indicates whether names are encoded and quoted for use in headers.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isEncodingNames(): bool
	{
		return $this->encodeNames;
	}

	/**
	 *	Renders addresses as list, separated by delimiter.
	 *	For headers: with names encoded (setEncodeNames) and lines folded between addresses (setLineLength).
	 *	@access		public
	 *	@param		AddressCollection	$collection		Addresses to render
	 *	@return		string
	 */
	public function render( AddressCollection $collection ): string
	{
		$renderer	= AddressRenderer::getInstance()
			->setEncodeName( $this->encodeNames )
			->setLineLength( $this->lineLength );
		if( 0 === $this->lineLength ){
			$list	= [];
			foreach( $collection->getAll() as $address )
				$list[]	= $renderer->render( $address );
			return join( $this->delimiter, $list );
		}

		//  fold between addresses, continuation lines start with a space (RFC 5322, 2.2.3)
		//  encoded names are rendered for their position in line, so lines with encoded words stay within limit (RFC 2047, 2)
		$separator	= rtrim( $this->delimiter );
		$value		= '';
		$length		= $this->offset;
		foreach( $collection->getAll() as $address ){
			$isFirst	= '' === $value;
			$start		= $isFirst ? $length : $length + strlen( $this->delimiter );
			$entry		= $renderer->render( $address, $start );
			$moveEntry	= FALSE;
			if( !$isFirst ){
				$tooLong	= $start + $this->getFirstLineLength( $entry ) > $this->lineLength;
				$isSplit	= str_contains( $entry, Message::$delimiter );
				if( $tooLong || $isSplit ){											//  next line, also if address would be split, but fits there
					$folded		= $renderer->render( $address, 1 );
					$moveEntry	= $tooLong || !str_contains( $folded, Message::$delimiter );
					if( $moveEntry ){
						$start	= 1;
						$entry	= $folded;
					}
				}
			}
			if( $moveEntry )
				$value	.= $separator.Message::$delimiter.' '.$entry;
			else
				$value	.= ( $isFirst ? '' : $this->delimiter ).$entry;
			$length		= MessageHeaderEncoding::getLastLineLength( $entry, $start );
		}
		return $value;
	}

	/**
	 *	Sets delimiter.
	 *	@access		public
	 *	@param		string		$delimiter
	 *	@return		self
	 *	@throws		InvalidArgumentException	if delimiter is whitespace or empty
	 */
	public function setDelimiter( string $delimiter ): self
	{
		if( !Str::hasContent( $delimiter ) )
			throw new InvalidArgumentException( 'Delimiter cannot be empty or whitespace' );
		$this->delimiter	= $delimiter;
		return $this;
	}

	/**
	 *	Sets whether to encode names (RFC 2047) and quote special characters, needed for use in headers.
	 *	@access		public
	 *	@param		boolean		$encode			Flag: encode names, default: no
	 *	@return		self
	 */
	public function setEncodeNames( bool $encode = TRUE ): self
	{
		$this->encodeNames	= $encode;
		return $this;
	}

	/**
	 *	Sets maximum line length, to fold between addresses, e.g. 76 for headers.
	 *	A single long address may exceed it.
	 *	@access		public
	 *	@param		integer		$length			Maximum line length, 0: no folding (default)
	 *	@return		self
	 */
	public function setLineLength( int $length ): self
	{
		$this->lineLength	= max( 0, $length );
		return $this;
	}

	/**
	 *	Sets length of text before the list in first line, e.g. 4 for "To: ", needed for folding.
	 *	@access		public
	 *	@param		integer		$offset			Length of header name, colon and space, default: 0
	 *	@return		self
	 */
	public function setOffset( int $offset ): self
	{
		$this->offset	= max( 0, $offset );
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Returns length of first line of a rendered, maybe folded address.
	 *	@access		protected
	 *	@param		string		$entry
	 *	@return		integer
	 */
	protected function getFirstLineLength( string $entry ): int
	{
		$position	= strpos( $entry, Message::$delimiter );
		return FALSE === $position ? strlen( $entry ) : $position;
	}
}
