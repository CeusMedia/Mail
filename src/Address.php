<?php
/**
 *	Data object for mail participants, having an address and optionally a name.
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;
/**
 *	Data object for mail participants, having an address and optionally a name.
 *
 *	Contains:
 *	- Address
 *		- Name (optional, default: NULL)
 *		- Address (virtual, Local Part + Domain)
 *			- Local Part (mandatory)
 *			- Domain (mandatory)
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 *	@todo			create tests
 *	@todo			use in Message class
 */
class Address{

	/**	@var	string		$domain			Domain of mail participant */
	protected $domain;

	/**	@var	string		$localPart		Local part of mail participant */
	protected $localPart;

	/**	@var	string		$name			Name of mail participant */
	protected $name;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$string		Full mail address to parse, optional
	 *	@return		void
	 */
	public function __construct( $string = NULL ){
		if( $string ){
			$this->set( $string );
		}
	}

	/**
	 *	Returns full address of mail participant with brackets and username (if available).
	 *	@access		public
	 *	@return		string		Full mail address with brackets and username (if available)
	 */
	public function get(){
		return self::render( $this->getDomain(), $this->getLocalPart(), $this->getName() );
	}

	/**
	 *	Returns short mail address (without name or brackets).
	 *	@access		public
	 *	@param		boolean		$strict		Flag: throw exception if no local part set
	 *	@return		string		Short mail address (without name or brackets)
	 */
	public function getAddress( $strict = TRUE ){
		return $this->getLocalPart( $strict )."@".$this->getDomain( $strict );
	}

	/**
	 *	Returns domain of mail participant.
	 *	@access		public
	 *	@param		boolean		$strict		Flag: throw exception if no domain set
	 *	@return		string		Domain of mail participant
	 *	@throws		RuntimeException		if no address has been set, yet
	 */
	public function getDomain( $strict = TRUE ){
		if( !$this->domain && $strict )
			throw new \RuntimeException( 'No address set, yet' );
		return $this->domain;
	}

	/**
	 *	Returns local part of mail participant.
	 *	@access		public
	 *	@param		boolean		$strict		Flag: throw exception if no local part set
	 *	@return		string		Local part of mail participant
	 *	@throws		RuntimeException		if no address has been set, yet
	 */
	public function getLocalPart( $strict = TRUE ){
		if( !$this->localPart && $strict )
			throw new \RuntimeException( 'No address set, yet' );
		return $this->localPart;
	}

	/**
	 *	Returns name of mail participant.
	 *	@access		public
	 *	@return		string		Name of mail participant
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 *	Renders full mail address by given parts.
	 *	Creates patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		string		$domain		Domain of mail address
	 *	@param		string		$localPart	Local part of mail address
	 *	@param		string		$name		Name of mail address
	 *	@return		string		Rendered mail address
	 *	@throws		InvalidArgumentException	if domain is empty
	 *	@throws		InvalidArgumentException	if local part is empty
	 */
	static public function render( $domain, $localPart, $name = NULL ){
		return \CeusMedia\Mail\Address\Renderer::render( $domain, $localPart, $name );
	}

	/**
	 *	Sets an full mail address.
	 *	@access		public
	 *	@param		string		$string			Full mail address to set
	 *	@return		void
	 *	@throws		InvalidArgumentException	if given string is empty
	 */
	public function set( $string ){
		if( !strlen( trim( $string ) ) )
			throw new InvalidArgumentException( 'No address given' );

		$address	= \CeusMedia\Mail\Address\Parser::parse( $string );
		$this->setDomain( $address->getDomain() );
		$this->setLocalPart( $address->getLocalPart() );
		$this->setName( $address->getName() );
	}

	/**
	 *	Sets domain of mail participant.
	 *	@access		public
	 *	@param		string		$domain			Domain of mail participant.
	 *	@return		void
	 */
	public function setDomain( $domain ){
		$this->domain	= $domain;
	}

	/**
	 *	Sets local part of of mail participant.
	 *	@access		public
	 *	@param		string		$localPart		Local part of of mail participant
	 *	@return		void
	 */
	public function setLocalPart( $localPart ){
		$this->localPart	= $localPart;
	}

	/**
	 *	Sets name of mail participant.
	 *	@access		public
	 *	@param		string		$name			Name of mail participant
	 *	@return		void
	 */
	public function setName( $name ){
		$this->name		= $name;
	}
}
?>
