<?php
/**
 *	Container for mail message header fields.
 *
 *	Copyright (c) 2007-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Header\Field as MessageHeaderField;

/**
 *	Container for mail message header fields.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Section{

	protected $fields			= array();

	/**
	 *	Add a header field object.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Message\Header\Field	$field		Header field object
	 *	@return		void
	 */
	public function addField( \CeusMedia\Mail\Message\Header\Field $field ){
		return $this->setField( $field, FALSE );
	}

	/**
	 *	Add a header field by pair.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@param		string		$value		Header Field value
	 *	@return		void
	 */
	public function addFieldPair( $name, $value ){
		$field	= new \CeusMedia\Mail\Message\Header\Field( $name, $value );
		$this->addField( $field );
	}

	/**
	 *	Add several header Field Objects.
	 *	@access		public
	 *	@param		array		$fields		List of header field objects
	 *	@return		void
	 */
	public function addFields( $fields ){
		$this->setFields( $fields, FALSE );
	}

	/**
	 *	Returns a header field object by its name if set.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		\CeusMedia\Mail\Message\Header\Field
	 *	@throws		\RangeException			if request header field name is not existing
	 */
	public function getField( $name ){
		if( !$this->hasField( $name ) )
			throw new \RangeException( 'Header "'.$name.'" is not available' );
		$values	= $this->getFieldsByName( $name );
		return array_shift( $values );
	}

	/**
	 *	Returns a kist of all set header field objects.
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
	 *	Returns a list of set header field objects for a header field name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		array
	 */
	public function getFieldsByName( $name ){
		$name	= strtolower( $name );
		if( isset( $this->fields[$name] ) )
			return $this->fields[$name];
		return array();
	}

	/**
	 *	Indicates whether a header field is set by its name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		boolean
	 */
	public function hasField( $name ){
		$name	= strtolower( $name );
		if( !isset( $this->fields[$name] ) )
			return FALSE;
		return (bool) count( $this->fields[$name] );
	}

	/**
	 *	Removes all header field objects by name.
	 *	@param		string		$name		Header field name
	 *	@return		Number of removed field objects
	 */
	public function removeFieldByName( $name ){
		$count	= count( $this->getFieldsByName( $name ) );
		if( $count )
			unset( $this->fields[strtolower( $name )] );
		return $count;
	}

	/**
	 *	Sets an header field object.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		\CeusMedia\Mail\Message\Header\Field	$field			Header field object to set
	 *	@param		boolean									$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		void
	 */
	public function setField( \CeusMedia\Mail\Message\Header\Field $field, $emptyBefore = TRUE ){
		$name	= strtolower( $field->getName() );
		if( $emptyBefore || !array_key_exists( $name, $this->fields ) )
			$this->fields[$name]	= array();
		$this->fields[$name][]	= $field;
	}

	/**
	 *	Sets an header field by name and value.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		string		$name			Header field name
	 *	@param		string		$value			Header field value
	 *	@param		boolean		$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		void
	 */
	public function setFieldPair( $name, $value, $emptyBefore = TRUE ){
		$field	= new MessageHeaderField( $name, $value );
		return $this->setField( $field, $emptyBefore );
	}

	/**
	 *	Sets several header field objects.
	 *	Headers with already noted name will be replaced.
	 *	To avoid this, disable emptyBefore.
	 *	@access		public
	 *	@param		array		$fields			List of header field objects
	 *	@param		boolean		$emptyBefore	Flag: TRUE - set | FALSE - append
	 *	@return		void
	 */
	public function setFields( $fields, $emptyBefore = TRUE ){
		foreach( $fields as $field )
			$this->setField( $field, $emptyBefore );
	}

	/**
	 *	Returns all header fields as list.
	 *	@access		public
	 *	@return		array
	 */
	public function toArray( $keepCase = FALSE ){
		$list	= array();
		foreach( $this->fields as $name => $fields )
			foreach( $fields as $field )
				$list[]	= $field->toString( $keepCase );
		return $list;
	}

	/**
	 *	Returns all set header fields as string.
	 *	@access		public
	 *	@return		string
	 */
	public function toString( $keepCase = FALSE ){
		$list	= $this->toArray( $keepCase );
		if( $list )
			return implode( Message::$delimiter, $list );
		return "";
	}
}
?>
