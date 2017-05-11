<?php
namespace CeusMedia\Mail\Address;
class Renderer{

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
		if( !strlen( trim( $domain ) ) )
			throw new \InvalidArgumentException( 'Domain cannot be empty' );
		if( !strlen( trim( $localPart ) ) )
			throw new \InvalidArgumentException( 'Local part cannot be empty' );
		if( !strlen( trim( $name ) ) )
			return $localPart.'@'.$domain;
		if( !preg_match( '/^\w+$/', $name ) )
			$name	= '"'.$name.'"';
		return $name.' <'.$localPart.'@'.$domain.'>';
	}
}
