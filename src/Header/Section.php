<?php
/**
 *	Container for Mail Header Fields.
 *
 *	Copyright (c) 2007-2016 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Header;
/**
 *	Container for Mail Header Fields.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Section{

	protected $fields			= array();

	/**
	 *	Add a Header Field Object.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Header\Field	$field		Header Field Object
	 *	@return		void
	 */
	public function addField( \CeusMedia\Mail\Header\Field $field ){
		return $this->setField( $field, FALSE );
	}

	/**
	 *	Add a Header Field by pair.
	 *	@access		public
	 *	@param		string		$name		Header Field Name
	 *	@param		string		$value		Header Field Value
	 *	@return		void
	 */
	public function addFieldPair( $name, $value ){
		$field	= new \CeusMedia\Mail\Header\Field( $name, $value );
		$this->addField( $field );
	}

	/**
	 *	Add several Header Field Objects.
	 *	@access		public
	 *	@param		array		$fields		List of Header Field Objects
	 *	@return		void
	 */
	public function addFields( $fields ){
		$this->setFields( $fields, FALSE );
	}

	/**
	 *	Returns a Header Field Object by its Name if set.
	 *	@access		public
	 *	@param		string		$name		Header Field Name
	 *	@return		CMM_Mail_Header_Field
	 */
	public function getField( $name ){
		if( !$this->hasField( $name ) )
			return NULL;
		$values	= $this->getFieldsByName( $name );
		return array_shift( $values );
	}

	/**
	 *	Returns a List of all set Header Field Objects.
	 *	@access		public
	 *	@return		array
	 */
	public function getFields(){
		$list	= array();
		foreach( $this->fields as $name => $fields )
			if( count( $fields ) )
				foreach( $fields as $field )
					$list[]	= $field;
		return $list;
	}

	/**
	 *	Returns a List of set Header Field Objects for a Header Field Name.
	 *	@access		public
	 *	@param		string		$name		Header Field Name
	 *	@return		array
	 */
	public function getFieldsByName( $name ){
		$name	= strtolower( $name );
		if( isset( $this->fields[$name] ) )
			return $this->fields[$name];
		return array();
	}

	/**
	 *	Indicates whether a Header Field is set by its Name.
	 *	@access		public
	 *	@param		string		$name		Header Field Name
	 *	@return		boolean
	 */
	public function hasField( $name ){
		$name	= strtolower( $name );
		if( !isset( $this->fields[$name] ) )
			return FALSE;
		return (bool) count( $this->fields[$name] );
	}

	/**
	 *	Removes all Header Field Objects by name.
	 *	@param		string		$name		Header Field Name
	 *	@return		Number of removed Field Objects
	 */
	public function removeFieldByName( $name ){
		$count	= count( $this->getFieldsByName( $name ) );
		if( $count )
			unset( $this->fields[strtolower( $name )] );
		return $count;
	}

	/**
	 *	Sets an Header Field Object.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Header\Field	$field			Header Field Object to set
	 *	@param		boolean					$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		void
	 */
	public function setField( \CeusMedia\Mail\Header\Field $field, $emptyBefore = TRUE ){
		$name	= strtolower( $field->getName() );
		if( $emptyBefore || !array_key_exists( $name, $this->fields ) )
			$this->fields[$name]	= array();
		$this->fields[$name][]	= $field;
	}

	/**
	 *	Sets an Header Field by Name and Value.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		string		$name			Header Field Name
	 *	@param		string		$value			Header Field Value
	 *	@param		boolean		$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		void
	 */
	public function setFieldPair( $name, $value, $emptyBefore = TRUE ){
		$field	= new \CeusMedia\Mail\Header\Field( $name, $value );
		return $this->setField( $field, $emptyBefore );
	}

	/**
	 *	Sets several Header Field Objects.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		array		$fields		List of Header Field Objects
	 *	@param		boolean		$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		void
	 */
	public function setFields( $fields, $emptyBefore = TRUE ){
		foreach( $fields as $field )
			$this->setField( $field, $emptyBefore );
	}

	/**
	 *	Returns all Header Fields as List.
	 *	@access		public
	 *	@return		array
	 */
	public function toArray(){
		$list	= array();
		foreach( $this->fields as $name => $fields )
			foreach( $fields as $field )
				$list[]	= $field->toString();
		return $list;
	}

	/**
	 *	Returns all set Header Fields as String.
	 *	@access		public
	 *	@return		string
	 */
	public function toString(){
		$list	= $this->toArray();
		if( $list )
			return implode( \CeusMedia\Mail\Message::$delimiter, $list )/*.\CeusMedia\Mail\Message::$delimiter*/;
		return "";
	}
}
?>
