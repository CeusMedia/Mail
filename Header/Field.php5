<?php
/**
 *	Mail Header Field Data Object.
 *
 *	Copyright (c) 2010-2014 Christian Würker (ceusmedia.de)
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
 *	@category		cmModules
 *	@package		Mail.Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id: Field.php5 961 2012-05-23 11:58:00Z christian.wuerker $
 */
/**
 *	Mail Header Field Data Object.
 *	@category		cmModules
 *	@package		Mail.Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@version		$Id: Field.php5 961 2012-05-23 11:58:00Z christian.wuerker $
 */
class CMM_Mail_Header_Field
{
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
	public function __construct( $name, $value )
	{
		$this->setName( $name );
		$this->setValue( $value );
	}

	/**
	 *	Returns set Header Name.
	 *	@access		public
	 *	@return		string		Header Name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *	Returns set Header Value.
	 *	@access		public
	 *	@return		string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 *	Sets Header Name.
	 *	@access		public
	 *	@param		string		$name		Header Field Name
	 *	@return		void
	 */
	public function setName( $name )
	{
		if( !trim( $name ) )
			throw new InvalidArgumentException( 'Field name cannot be empty' );
		$this->name	= strtolower( $name );
	}


	/**
	 *	Sets Header Value.
	 *	@access		public
	 *	@param		string		$name		Header Field Value
	 *	@return		void
	 */
	public function setValue( $value )
	{
		$this->value	= $value;
	}

	/**
	 *	Returns a representative string of Header.
	 *	@access		public
	 *	@return		string
	 */
	public function toString()
	{
		$name	= mb_convert_case( $this->name, MB_CASE_TITLE );
		return $name.": ".$this->value;
	}

	/**
	 *	Alias for toString().
	 *	@access		public
	 *	@return		string
	 */
	public function __toString()
	{
		return $this->toString();
	}
}
?>