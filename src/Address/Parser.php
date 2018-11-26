<?php
namespace CeusMedia\Mail\Address;

use \CeusMedia\Mail\Address;

class Parser{

	/**	@var	array		$patterns		Map of understandable patterns (regular expressions) */
	static protected $patterns	= array(												//  define name patterns
		'name <local-part@domain>'	=> "/^(.*)\s(<((\S+)@(\S+))>)$/U",					//  full address: name and local-part at domain with (maybe in brackets)
		'<local-part@domain>'		=> "/^<((\S+)@(\S+))>$/U",							//  short address: local-part at domain without name (and no brackets)
		'local-part@domain'			=> "/^((\S+)@(\S+))$/U",							//  short address: local-part at domain without name (and no brackets)
	);

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function create(){
		return new static();
	}

	/**
	 *	Parse a mail address and return found parts.
	 *	Reads patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	@access		public
	 *	@param		string		$string			Mail address to parse
	 *	@return		Address		Address object
	 *	@throws		\InvalidArgumentException	if given string is not a valid mail address
	 */
	public function parse( $string ){
		$string		= stripslashes( trim( $string ) );
		$string		= preg_replace( "/\r\n /", " ", $string );							//  unfold @see http://tools.ietf.org/html/rfc822#section-3.1
		$regex1		= self::$patterns['name <local-part@domain>'];						//  get pattern of full address
		$regex2		= self::$patterns['<local-part@domain>'];							//  get pattern of short address
		$regex3		= self::$patterns['local-part@domain'];								//  get pattern of short address
		$name		= '';
		if( preg_match( $regex1, $string ) ){											//  found full address: with name or in brackets
			$localPart	= preg_replace( $regex1, "\\4", $string );						//  extract local part
			$domain		= preg_replace( $regex1, "\\5", $string );						//  extract domain part
			$name		= trim( preg_replace( $regex1, "\\1", $string ) );				//  extract user name
			$name		= preg_replace( "/^\"(.+)\"$/", "\\1", $name );					//  strip quotes from user name
		}
		else if( preg_match( $regex2, $string ) ){										//  otherwise found short address: neither name nor brackets
			$localPart	= preg_replace( $regex2, "\\2", $string );						//  extract local part
			$domain		= preg_replace( $regex2, "\\3", $string );						//  extract domain part
		}
		else if( preg_match( $regex3, $string ) ){										//  otherwise found short address: neither name nor brackets
			$localPart	= preg_replace( $regex3, "\\2", $string );						//  extract local part
			$domain		= preg_replace( $regex3, "\\3", $string );						//  extract domain part
		}
		else{																			//  not matching any pattern
			$list		= '"'.implode( '" or "', array_keys( self::$patterns ) ).'"';
			$message	= 'Invalid address given (must match '.$list.') - got '.$string ;
			throw new \InvalidArgumentException( $message );
		}
		$address	= new Address();
		$address->setDomain( $domain );
		$address->setLocalPart( $localPart );
		$address->setName( $name );
		return $address;
	}
}
