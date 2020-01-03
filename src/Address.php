<?php
/**
 *	Data object for mail participants, having an address and optionally a name.
 *
 *	Copyright (c) 2007-2020 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use \CeusMedia\Mail\Address\Parser as AddressParser;
use \CeusMedia\Mail\Address\Renderer as AddressRenderer;
use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;

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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			code doc
 *	@todo			create tests
 *	@todo			use in Message class
 */
class Address
{
	/**	@var	string		$domain			Domain of mail participant */
	protected $domain;

	/**	@var	string		$localPart		Local part of mail participant */
	protected $localPart;

	/**	@var	string		$name			Name of mail participant */
	protected $name;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string|NULL		$string		Full mail address to parse, optional
	 *	@return		void
	 */
	public function __construct( string $string = NULL )
	{
		if( $string ){
			$this->set( $string );
		}
	}

	/**
	 *	Returns full address of mail participant with brackets and username (if available).
	 *	Alias for get.
	 *	@access		public
	 *	@return		string		Full mail address with brackets and username (if available)
	 */
	public function __toString(): string
	{
		return $this->get();
	}

	/**
	 *	Alias for getInstance.
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 */
	public static function create( string $string = NULL ): self
	{
		return new static( $string );
	}

	/**
	 *	Returns full address of mail participant with brackets and username (if available).
	 *	@access		public
	 *	@return		string		Full mail address with brackets and username (if available)
	 */
	public function get(): string
	{
		return self::render( $this );
	}

	/**
	 *	Returns short mail address (without name or brackets).
	 *	@access		public
	 *	@param		boolean			$strict		Flag: throw exception if no local part set
	 *	@return		string			Short mail address (without name or brackets)
	 */
	public function getAddress( bool $strict = TRUE ): string
	{
		return $this->getLocalPart( $strict )."@".$this->getDomain( $strict );
	}

	/**
	 *	Returns domain of mail participant.
	 *	@access		public
	 *	@param		boolean			$strict		Flag: throw exception if no domain set
	 *	@return		string			Domain of mail participant
	 *	@throws		\RuntimeException			if no address has been set, yet
	 */
	public function getDomain( bool $strict = TRUE ): string
	{
		if( !$this->domain && $strict )
			throw new \RuntimeException( 'No valid address set, yet (domain is missing)' );
		return (string) $this->domain;
	}

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@param		string|NULL		$string		Full mail address to parse, optional
	 *	@return		self
	 */
	public static function getInstance( string $string = NULL ): self
	{
		return new static( $string );
	}

	/**
	 *	Returns local part of mail participant.
	 *	@access		public
	 *	@param		boolean			$strict		Flag: throw exception if no local part set
	 *	@return		string			Local part of mail participant
	 *	@throws		\RuntimeException			if no address has been set, yet
	 */
	public function getLocalPart( bool $strict = TRUE ): string
	{
		if( !$this->localPart && $strict )
			throw new \RuntimeException( 'No valid address set, yet (local part missing)' );
		return (string) $this->localPart;
	}

	/**
	 *	Returns name of mail participant.
	 *	@access		public
	 *	@return		string		Name of mail participant
	 */
	public function getName(): string
	{
		return (string) $this->name;
	}

	/**
	 *	Renders full mail address by given parts.
	 *	Creates patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		Address		$address	Address to render
	 *	@return		string
	 *	@throws		\RuntimeException		If domain is empty
	 *	@throws		\RuntimeException		If local part is empty
	 *	@todo		Check behaviour: render methods always return member data, why with argument here?
	 */
	public function render( Address $address ): string
	{
		return AddressRenderer::getInstance()->render( $address );
	}

	/**
	 *	Sets an full mail address.
	 *	@access		public
	 *	@param		string		$string			Full mail address to set
	 *	@return		self		Own instance for chainability
	 *	@throws		\InvalidArgumentException	if given string is empty
	 */
	public function set( string $string ): self
	{
		if( !strlen( trim( $string ) ) )
			throw new \InvalidArgumentException( 'No address given' );

		$address	= AddressParser::getInstance()->parse( $string );
		$this->setDomain( $address->getDomain() );
		$this->setLocalPart( $address->getLocalPart() );
		$this->setName( $address->getName() );
		return $this;
	}

	/**
	 *	Sets domain of mail participant.
	 *	@access		public
	 *	@param		string		$domain			Domain of mail participant.
	 *	@return		self		Own instance for chainability
	 */
	public function setDomain( string $domain ): self
	{
		$this->domain	= $domain;
		return $this;
	}

	/**
	 *	Sets local part of of mail participant.
	 *	@access		public
	 *	@param		string		$localPart		Local part of of mail participant
	 *	@return		self		Own instance for chainability
	 */
	public function setLocalPart( string $localPart ): self
	{
		$this->localPart	= $localPart;
		return $this;
	}

	/**
	 *	Sets name of mail participant.
	 *	@access		public
	 *	@param		string		$name			Name of mail participant
	 *	@return		self		Own instance for chainability
	 */
	public function setName( string $name ): self
	{
		$this->name		= MessageHeaderEncoding::decodeIfNeeded( $name );
		return $this;
	}
}
