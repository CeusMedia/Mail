<?php
/**
 *	Mail Header Field Data Object.
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
 *	Mail Header Field Data Object.
 *	@category		Library
 *	@package		CeusMedia_Mail_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Field{
	/**	@var		string		$name		Name of Header */
	protected $name;
	/**	@var		string		$value		Value of Header */
	protected $value;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$name		Name of Header
	 *	@param		string		$value		Value of Header
	 *	@return		void
	 */
	public function __construct( $name, $value ){
		$this->setName( $name );
		$this->setValue( $value );
	}

	/**
	 *	Returns set Header Name.
	 *	By default all letters will be lowercased, before the first word letter is uppercased.
	 *	If available mb_convert_case is used. Otherwise ucwords will do the trick.
	 *	This will lowercase acronyms, too.
	 *	You can protected uppercased acronyms by keeping the letter case, using the first argument.
	 *	@access		public
	 *	@param		boolean		$keepCase				Flag: do not use mb_convert_case or ucwords, protected uppercased acronyms
	 *	@param		boolean		$ignoreMbConvertCase	Flag: do not use mb_convert_case even if it is available (needed to testing)
	 *	@return		string		Header Name
	 */
	public function getName( $keepCase = FALSE, $ignoreMbConvertCase = FALSE ){
		if( !$keepCase ){
			if( function_exists( 'mb_convert_case' ) && !$ignoreMbConvertCase )
				return mb_convert_case( $this->name, MB_CASE_TITLE );
			return str_replace( " ", "-", ucwords( str_replace( "-", " ", strtolower( $this->name ) ) ) );
		}
		return str_replace( " ", "-", ucwords( str_replace( "-", " ", $this->name ) ) );
	}

	/**
	 *	Returns set Header Value.
	 *	@access		public
	 *	@return		string
	 */
	public function getValue(){
		return $this->value;
	}

	/**
	 *	Sets Header Name.
	 *	@access		public
	 *	@param		string		$name		Header Field Name
	 *	@return		void
	 */
	public function setName( $name ){
		if( !trim( $name ) )
			throw new \InvalidArgumentException( 'Field name cannot be empty' );
		$this->name	= preg_replace( "/( |-)+/", "-", trim( $name ) );
	}


	/**
	 *	Sets Header Value.
	 *	@access		public
	 *	@param		string		$name		Header Field Value
	 *	@return		void
	 */
	public function setValue( $value ){
		$this->value	= $value;
	}

	/**
	 *	Returns a representative string of Header.
	 *	@access		public
	 *	@return		string
	 */
	public function toString(){
		return $this->getName().": ".$this->getValue();
	}

	/**
	 *	Alias for toString().
	 *	@access		public
	 *	@return		string
	 */
	public function __toString(){
		return $this->toString();
	}
}
?>
