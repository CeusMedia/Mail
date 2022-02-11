<?php
declare(strict_types=1);

/**
 *	Container for mail message header fields.
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

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Field as MessageHeaderField;
use Countable;
use RangeException;

use function array_shift;
use function count;
use function strtolower;

/**
 *	Container for mail message header fields.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Section implements Countable
{
	/**	@var	array<Field[]>		$fields */
	protected $fields				= [];

	/**
	 *	Add a header field object.
	 *	@access		public
	 *	@param		Field		$field		Header field object
	 *	@return		self
	 */
	public function addField( Field $field ): self
	{
		return $this->setField( $field, FALSE );
	}

	/**
	 *	Add a header field by pair.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@param		string		$value		Header Field value
	 *	@return		self
	 */
	public function addFieldPair( string $name, string $value ): self
	{
		$field	= new Field( $name, $value );
		return $this->addField( $field );
	}

	/**
	 *	Add several header Field Objects.
	 *	@access		public
	 *	@param		array		$fields		List of header field objects
	 *	@return		self
	 */
	public function addFields( array $fields ): self
	{
		return $this->setFields( $fields, FALSE );
	}

	/**
	 *	Returns number of header fields in section.
	 *	Implements countable interface.
	 *	@access		public
	 *	@return		integer		Number of header fields in section.
	 */
	public function count(): int
	{
		return count( $this->fields );
	}

	/**
	 *	Returns a header field object by its name if set.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		Field
	 *	@throws		RangeException			if request header field name is not existing
	 */
	public function getField( string $name ): Field
	{
		if( !$this->hasField( $name ) )
			throw new RangeException( 'Header "'.$name.'" is not available' );
		$values	= $this->getFieldsByName( $name );
		return array_shift( $values );
	}

	/**
	 *	Returns a list of all set header field objects.
	 *	@access		public
	 *	@return		array
	 */
	public function getFields(): array
	{
		$list	= [];
		foreach( $this->fields as $name => $fields )
			if( 0 < count( $fields ) )
				foreach( $fields as $field )
					$list[]	= $field;
		return $list;
	}

	/**
	 *	Returns a list of set header field objects for a header field name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		array
	 */
	public function getFieldsByName( string $name ): array
	{
		$name	= strtolower( $name );
		if( isset( $this->fields[$name] ) )
			return $this->fields[$name];
		return [];
	}

	/**
	 *	Indicates whether a header field is set by its name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		boolean
	 */
	public function hasField( string $name ): bool
	{
		$name	= strtolower( $name );
		if( !isset( $this->fields[$name] ) )
			return FALSE;
		return (bool) count( $this->fields[$name] );
	}

	/**
	 *	Removes all header field objects by name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		integer 	Number of removed field objects
	 */
	public function removeFieldByName( string $name ): int
	{
		$count	= count( $this->getFieldsByName( $name ) );
		if( 0 < $count )
			unset( $this->fields[strtolower( $name )] );
		return $count;
	}

	/**
	 *	Sets an header field object.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		Field			$field			Header field object to set
	 *	@param		boolean|NULL	$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		self
	 */
	public function setField( Field $field, ?bool $emptyBefore = TRUE ): self
	{
		$name	= strtolower( $field->getName() );
		if( (bool) $emptyBefore || !array_key_exists( $name, $this->fields ) )
			$this->fields[$name]	= [];
		$this->fields[$name][]	= $field;
		return $this;
	}

	/**
	 *	Sets an header field by name and value.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		string			$name			Header field name
	 *	@param		string			$value			Header field value
	 *	@param		boolean|NULL	$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		self
	 */
	public function setFieldPair( string $name, string $value, ?bool $emptyBefore = TRUE ): self
	{
		$field	= new MessageHeaderField( $name, $value );
		return $this->setField( $field, $emptyBefore );
	}

	/**
	 *	Sets several header field objects.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		array			$fields			List of header field objects
	 *	@param		boolean|NULL	$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		self
	 */
	public function setFields( array $fields, ?bool $emptyBefore = TRUE ): self
	{
		foreach( $fields as $field )
			$this->setField( $field, $emptyBefore );
		return $this;
	}

	/**
	 *	Returns all header fields as list.
	 *	@access		public
	 *	@param		boolean			$keepCase		Flag: keep original field key (default: no)
	 *	@return		array
	 */
	public function toArray( bool $keepCase = FALSE ): array
	{
		$list	= [];
		foreach( $this->fields as $name => $fields )
			foreach( $fields as $field )
				$list[]	= $field->toString( $keepCase );
		return $list;
	}

	/**
	 *	Returns all set header fields as string.
	 *	@access		public
	 *	@param		boolean			$keepCase		Flag: keep original field key (default: no)
	 *	@return		string
	 */
	public function toString( bool $keepCase = FALSE ): string
	{
		$list	= $this->toArray( $keepCase );
		if( 0 < count( $list ) )
			return implode( Message::$delimiter, $list );
		return "";
	}
}
