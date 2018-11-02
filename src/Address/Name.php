<?php
namespace CeusMedia\Mail\Address;

class Name{

	static public function splitNameParts( $list ){
		foreach( $list as $nr => $entry ){
			if( preg_match( "/ +/", $entry['fullname'] ) ){
				$parts	= preg_split( "/ +/", $entry['fullname'] );
				$list[$nr]['surname']	= array_pop( $parts );
				$list[$nr]['firstname']	= join( ' ', $parts );
			}
		}
		return $list;
	}

	static public function swapCommaSeparatedNameParts( $list ){
		foreach( $list as $nr => $entry ){
			if( preg_match( "/, +/", $entry['fullname'] ) ){
				$parts	= preg_split( "/, +/", $entry['fullname'], 2 );
				$list[$nr]['fullname']	= $parts[1].' '.$parts[0];
			}
		}
		return $list;
	}
}
?>

}
