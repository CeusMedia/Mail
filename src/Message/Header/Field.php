<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Mail message header field data object.
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
use CeusMedia\Mail\Conduct\RegularStringHandling;
use CeusMedia\Mail\Message\Header\Renderer as MessageHeaderRenderer;

use InvalidArgumentException;

use function function_exists;
use function mb_convert_case;
use function str_replace;
use function strlen;
use function strtolower;
use function trim;
use function ucwords;

/**
 *	Mail message header field data object.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Field
{
	use RegularStringHandling;

	/**	@var		Dictionary	$attributes		Dictionary of header attributes */
	protected Dictionary $attributes;

	/**	@var		string		$name		Name of header */
	protected string $name;

	/**	@var		string		$value		Value of header */
	protected string $value;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$name		Name of header
	 *	@param		string		$value		Value of header
	 *	@param		array		$attributes	Map of header attributes
	 *	@return		void
	 */
	public function __construct( string $name, string $value, array $attributes = [] )
	{
		$this->setName( $name );
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
		return $this->toString( TRUE );
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
		$value	= $this->attributes->get( $name, $default );
		if( !is_string( $value ) )
			return NULL;
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
	 *	Returns set header name.
	 *	By default all letters will be lowercased, before the first word letter is uppercased.
	 *	If available mb_convert_case is used. Otherwise ucwords will do the trick.
	 *	This will lowercase acronyms, too.
	 *	You can protected uppercased acronyms by keeping the letter case, using the first argument.
	 *	@access		public
	 *	@param		boolean		$keepCase				Flag: do not use mb_convert_case or ucwords, protected uppercased acronyms
	 *	@param		boolean		$ignoreMbConvertCase	Flag: do not use mb_convert_case even if it is available (needed to testing)
	 *	@return		string		Header name
	 */
	public function getName( bool $keepCase = TRUE, bool $ignoreMbConvertCase = FALSE ): string
	{
		if( $keepCase )
			return $this->name;
		if( function_exists( 'mb_convert_case' ) && !$ignoreMbConvertCase )
			return mb_convert_case( $this->name, MB_CASE_TITLE );
		return str_replace( " ", "-", ucwords( str_replace( "-", " ", strtolower( $this->name ) ) ) );
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
	public function setAttribute( string $name, string $value ): self
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
	 *	Sets header name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		self
	 */
	public function setName( string $name ): self
	{
		if( 0 === strlen( trim( $name ) ) )
			throw new InvalidArgumentException( 'Field name cannot be empty' );
		$this->name	= self::regReplace( "/( |-)+/", "-", trim( $name ) );
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
	 *	Returns a representative string of header.
	 *	@access		public
	 *	@param		boolean		$keepCase		...
	 *	@return		string
	 */
	public function toString( bool $keepCase = TRUE ): string
	{
		return MessageHeaderRenderer::renderField( $this, $keepCase );
	}
}
