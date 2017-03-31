<?php
namespace CeusMedia\Mail\Address;
class Parser{

	/**
	 *	Parse a mail address and return found parts.
	 *	Reads patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		string		$string			Mail address to parse
	 *	@return		void
	 *	@throws		InvalidArgumentException	if given string is not a valid mail address
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
			$list		= '"'.implode( '" or "', array_keys( self::$patterns ) ).'"';
			$message	= 'Invalid address given (must match '.$list.')' ;
			throw new \InvalidArgumentException( $message );
		}
		return (object) array(
			'name'		=> $name,
			'address'	=> $localPart.'@'.$domain,
			'localPart'	=> $localPart,
			'domain'	=> $domain,
		);
	}
}
