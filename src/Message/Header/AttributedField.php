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

use ADT_List_Dictionary as Dictionary;

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
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class AttributedField extends Field
{
	/**	@var		Dictionary	$attributes		Dictionary of header attributes */
	protected $attributes;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$name			Name of header
	 *	@param		string		$value			Value of header
	 *	@param		array		$attributes		Map of header attributes
	 *	@return		void
	 */
	public function __construct( string $name, string $value, array $attributes = [] )
	{
		parent::__construct( $name, $value );
		$this->setAttributes( $attributes );
	}

	/**
	 *	Returns value of header attribute, if set. NULL otherwise.
	 *	@access		public
	 *	@param		string		$name			Name of header attribute to get
	 *	@param		mixed		$default		Optional: Value to return if key is not set
	 *	@return		string|NULL	Value of header attribute (if set) or NULL otherwise
	 */
	public function getAttribute( string $name, $default = NULL ): ?string
	{
		return $this->attributes->get( $name, $default );
	}

	/**
	 *	Returns set header attributes as array.
	 *	@access		public
	 *	@return		array		Header attributes as dictionary
	 */
	public function getAttributes(): array
	{
		return $this->attributes->getAll();
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
	 *	Returns a representative string of header.
	 *	@access		public
	 *	@param		boolean|NULL	$keepCase	Flag: do not use mb_convert_case or ucwords, protected uppercased acronyms
	 *	@return		string
	 */
	public function toString( ?bool $keepCase = TRUE ): string
	{
		$attr	= '';
		if( count( $this->attributes ) !== 0 )
			foreach( $this->attributes->getAll() as $key => $value )
				$attr	.= sprintf( ' %s="%s"', $key, $value );
		return $this->getName( $keepCase ).": ".$this->getValue().$attr;
	}
}
