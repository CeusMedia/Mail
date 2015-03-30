<?php
class CMM_Mail_Check_Address{

	static protected $regexSimple	= "@^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})$@";
	static protected $regexExtended	= "@^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$@";

	static public function check( $address, $throwException = TRUE ){
		if( preg_match( self::$regexSimple, $address ) ){
			return 1;
		}
		if( preg_match( self::$regexExtended, $address ) ){
			return 2;
		}
		if( $throwException ){
			throw new InvalidArgumentException( 'Given mail address is not valid' );
		}
		return 0;
	}

	static public function isValid( $address ){
		return self::check( $address, FALSE ) > 0;
	}
}
?>
