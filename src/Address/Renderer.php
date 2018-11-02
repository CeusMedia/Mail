<?php
namespace CeusMedia\Mail\Address;

use \CeusMedia\Mail\Address;

class Renderer{

	/**
	 *	Renders full mail address by given parts.
	 *	Creates patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		Address		$address		Address to render
	 *	@return		string		Rendered mail address
	 *	@throws		\RuntimeException			If domain is empty
	 *	@throws		\RuntimeException			If local part is empty
	 */
	static public function render( Address $address ){
		$domain		= $address->getDomain();
		$localPart	= $address->getLocalPart();
		$name		= $address->getName();
		if( !strlen( trim( $name ) ) )
			return $localPart.'@'.$domain;
		if( !preg_match( '/^\w+$/', $name ) )
			$name	= '"'.$name.'"';
		return $name.' <'.$localPart.'@'.$domain.'>';
	}
}
