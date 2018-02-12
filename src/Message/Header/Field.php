<?php
/**
 *	Mail message header field data object.
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;
/**
 *	Mail message header field data object.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Field{
	/**	@var		string		$name		Name of header */
	protected $name;
	/**	@var		string		$value		Value of header */
	protected $value;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$name		Name of header
	 *	@param		string		$value		Value of header
	 *	@return		void
	 */
	public function __construct( $name, $value ){
		$this->setName( $name );
		$this->setValue( $value );
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
	public function getName( $keepCase = FALSE, $ignoreMbConvertCase = FALSE ){
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
	public function getValue( $fold = FALSE ){
		if( !$fold )
			return $this->value;
		$maxLength	= \CeusMedia\Mail\Message::$lineLength;
		$delim		= \CeusMedia\Mail\Message::$delimiter;
		if( strlen( $this->value ) < $maxLength )
			return $this->value;
		return wordwrap( $this->value, $maxLength, $delim.' ', TRUE );
	}

	/**
	 *	Sets header name.
	 *	@access		public
	 *	@param		string		$name		Header field name
	 *	@return		void
	 */
	public function setName( $name ){
		if( !trim( $name ) )
			throw new \InvalidArgumentException( 'Field name cannot be empty' );
		$this->name	= preg_replace( "/( |-)+/", "-", trim( $name ) );
	}


	/**
	 *	Sets header value.
	 *	@access		public
	 *	@param		string		$name		Header field value
	 *	@return		void
	 */
	public function setValue( $value ){
		$this->value	= $value;
	}

	/**
	 *	Returns a representative string of header.
	 *	@param		boolean		$keepCase		...
	 *	@param		boolean		$fold			...
	 *	@access		public
	 *	@return		string
	 */
	public function toString( $keepCase = TRUE, $fold = TRUE ){
		return $this->getName( $keepCase ).": ".$this->getValue( $fold );
	}

	/**
	 *	Alias for toString().
	 *	@access		public
	 *	@return		string
	 */
	public function __toString(){
		return $this->toString( TRUE, TRUE );
	}
}
?>
