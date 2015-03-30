<?php
/**
 *	Data object for mail participants, having an address and optionally a name.
 *
 *	Copyright (c) 2007-2013 Christian Würker (ceusmedia.de)
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
 *	@package		Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id$
 */
/**
 *	Data object for mail participants, having an address and optionally a name.
 *
 *	Contains:
 *	- Participant
 *		- Name (optional, default: NULL)
 *		- Address (virtual, Local Part + Domain)
 *			- Local Part (mandatory)
 *			- Domain (mandatory)
 *
 *	@category		cmModules
 *	@package		Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id$
 *	@todo			code doc
 *	@todo			create tests
 *	@todo			use in Message class
 */
class CMM_Mail_Participant{

	/**	@var	string		$domain			Domain of mail participant */
	protected $domain;

	/**	@var	string		$localPart		Local part of mail participant */
	protected $localPart;

	/**	@var	string		$name			Name of mail participant */
	protected $name;

	/**	@var	array		$patterns		Map of understandable patterns (regular expressions) */
	static protected $patterns	= array(												//  define name patterns
		'name <local-part@domain>'	=> "/^(.*)\s(<((\S+)@(\S+))>)$/U",					//  full address: name and local-part at domain with (maybe in brackets)
		'local-part@domain'			=> "/^((\S+)@(\S+))$/U",							//  short address: local-part at domain without name (and no brackets)
	);

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$string		Full mail address to parse, optional
	 *	@return		void
	 */
	public function __construct( $string = NULL ){
		if( $string ){
			$parts	= $this->parse( $string );
			$this->domain		= $parts->domain;
			$this->localPart	= $parts->localPart;
			$this->name			= $parts->name;
		}
	}

	/**
	 *	Returns full address of mail participant with brackets and username (if available).
	 *	@access		public
	 *	@return		string		Full mail address with brackets and username (if available)
	 */
	public function get(){
		return self::render( $this->domain, $this->localPart, $this->name );
	}

	/**
	 *	Returns short mail address (without name or brackets).
	 *	@access		public
	 *	@return		string		Short mail address (without name or brackets)
	 */
	public function getAddress(){
		return $this->getLocalPart()."@".$this->getDomain();
	}

	/**
	 *	Returns domain of mail participant.
	 *	@access		public
	 *	@return		string		Domain of mail participant
	 *	@throws		RuntimeException		if no address has been set, yet
	 */
	public function getDomain(){
		if( !$this->domain )
			throw new RuntimeException( 'No address set, yet' );
		return $this->domain;
	}

	/**
	 *	Returns local part of mail participant.
	 *	@access		public
	 *	@return		string		Local part of mail participant
	 *	@throws		RuntimeException		if no address has been set, yet
	 */
	public function getLocalPart(){
		if( !$this->localPart )
			throw new RuntimeException( 'No address set, yet' );
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
	 *	.
	 *	@access		public
	 *	@param		.		$.		.
	 *	@return		void
	 */
	static public function parse( $string ){
		$string		= stripslashes( trim( $string ) );
		$string		= preg_replace( "/\r\n /", " ", $string );							//  unfold @see http://tools.ietf.org/html/rfc822#section-3.1
		$regex1		= self::$patterns['name <local-part@domain>'];						//  get pattern of full address
		$regex2		= self::$patterns['local-part@domain'];								//  get pattern of short address
		if( preg_match( $regex1, $string ) ){											//  found full address: with name or in brackets
			$localPart	= preg_replace( $regex1, "\\4", $string );						//  extract local part
			$domain		= preg_replace( $regex1, "\\5", $string );						//  extract domain part
			$name		= trim( preg_replace( $regex1, "\\1", $string ) );				//  extract user name
			$name		= preg_replace( "/^\"(.+)\"$/", "\\1", $name );					//  strip quotes from user name
		}
		else if( preg_match( $regex2, $string ) ){										//  otherwise found short address: neither name nor brackets
			$localPart	= preg_replace( $regex2, "\\2", $string );						//  extract local part
			$domain		= preg_replace( $regex2, "\\3", $string );						//  extract domain part
			$name		= NULL;															//  clear user name
		}
		else{																			//  not matching any pattern
			$list	= '"'.implode( '" or "', array_keys( self::$patterns ) ).'"';
			throw new InvalidArgumentException( 'Invalid address given (must match '.$list.')' );
		}
		return (object) array(
			'name'		=> $name,
			'address'	=> $localPart.'@'.$domain,
			'localPart'	=> $localPart,
			'domain'	=> $domain,
		);
	}

	/**
	 *	.
	 *	@access		public
	 *	@param		.		$.		.
	 *	@return		void
	 */
	static public function render( $domain, $localPart, $name = NULL ){
		if( !strlen( trim( $domain ) ) )
			throw new InvalidArgumentException( 'Domain cannot be empty' );
		if( !strlen( trim( $localPart ) ) )
			throw new InvalidArgumentException( 'Local part cannot be empty' );
		if( strlen( trim( $name ) ) )
			return addslashes( $name.' <'.$localPart.'@'.$domain.'>' );
		return addslashes( $localPart.'@'.$domain );
	}

	/**
 	 *	Sets of mail address by understanding a short address: local part and domain without name and brackets.
	 *	Attention: Resets previously set name, since short address has no name.
	 *	@access		public
	 *	@param		string		$address		Mail address (short) to set
	 *	@return		void
	 *	@throws		InvalidArgumentException	if given address is not matching pattern "local-part@domain"
	 */
	public function setAddress( $address ){
		$address	= trim( $address );
		$regex		= self::$patterns['local-part@domain'];
		if( !preg_match( $regex, trim( $address ) ) )									//  found short address: neither name nor brackets
			throw new InvalidArgumentException( 'Invalid address given (must match "local-part@domain")' );
		$this->localPart	= preg_replace( $regex, "\\2", $address );					//  extract local part
		$this->domain		= preg_replace( $regex, "\\3", $address );					//  extract domain part
		$this->name		= NULL;															//  clear user name
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
