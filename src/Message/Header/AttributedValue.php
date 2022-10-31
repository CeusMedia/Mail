<?php
declare(strict_types=1);

/**
 *	Mail message header field data object supporting attributes.
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Mail\Message\Header\Renderer as MessageHeaderRenderer;

use function count;
use function sprintf;

/**
 *	Mail message header field data object supporting attributes.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class AttributedValue
{
	/**	@var		Dictionary	$attributes		Dictionary of header attributes */
	protected Dictionary $attributes;

	/**	@var		string		$value			Value of header */
	protected string $value;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$value			Value of header
	 *	@param		array		$attributes		Map of header attributes
	 *	@return		void
	 */
	public function __construct( string $value, array $attributes = [] )
	{
		$this->setValue( $value );
		$this->setAttributes( $attributes );
	}

	/**
	 *	Alias for toString().
	 *	@access		public
	 *	@return		string
	 */
	public function __toString(): string
	{
		return $this->toString();
	}

	/**
	 *	Returns value of header attribute as string, if set. NULL otherwise.
	 *
	 *	@access		public
	 *	@param		string		$name			Name of header attribute to get
	 *	@param		mixed		$default		Optional: Value to return if key is not set
	 *	@return		string|NULL	Value of header attribute (if set) or NULL otherwise
	 */
	public function getAttribute( string $name, $default = NULL ): ?string
	{
		/** @var string|NULL $value */
		$value	= $this->attributes->get( $name, $default );
		return $value;
	}

	/**
	 *	Returns set header attributes as array.
	 *	@access		public
	 *	@return		array		Header attributes as dictionary
	 */
	public function getAttributes(): array
	{
		return (array) $this->attributes->getAll();
	}

	/**
	 *	Indicates whether header attributes are set.
	 *	@access		public
	 *	@return		boolean
	 */
	public function hasAttributes(): bool
	{
		return 0 !== count( $this->attributes );
	}

	/**
	 *	Returns set header value.
	 *	@access		public
	 *	@return		string		Header value
	 */
	public function getValue(): string
	{
		return $this->value;
	}

	/**
	 *	Sets header attribute.
	 *	@access		public
	 *	@param		string		$name			Name of header attribute
	 *	@param		string		$value			Value of header attribute
	 *	@return		self
	 */
	public function setAttribute( $name, $value ): self
	{
		$this->attributes->set( $name, $value );
		return $this;
	}

	/**
	 *	Sets header attributes.
	 *	@access		public
	 *	@param		array		$attributes		Map of header attributes
	 *	@return		self
	 */
	public function setAttributes( array $attributes ): self
	{
		$this->attributes	= new Dictionary( $attributes );
		return $this;
	}

	/**
	 *	Sets header value.
	 *	@access		public
	 *	@param		string		$value		Header field value
	 *	@return		self
	 */
	public function setValue( string $value ): self
	{
		$this->value	= $value;
		return $this;
	}

	/**
	 *	Returns a representative string of header field value.
	 *	@access		public
	 *	@return		string
	 */
	public function toString(): string
	{
		return MessageHeaderRenderer::renderAttributedValue( $this );
	}
}
