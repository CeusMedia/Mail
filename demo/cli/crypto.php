<?php
require_once __DIR__.'/../../vendor/autoload.php';

new \CeusMedia\Common\UI\DevOutput();

$prefix		= 'STREAM_CRYPTO_METHOD_';
$suffix		= '_CLIENT';

$constants	= \CeusMedia\Common\ADT\Constant::getAll( $prefix );
$constants	= array_filter( $constants, function( $content ){
	return FALSE === preg_match( '/_SERVER$/', $content );
}, ARRAY_FILTER_USE_KEY );

remark( 'PHP: '.phpversion().'' );
foreach( $constants as $cKey => $cValue ){
	$cKey	= preg_replace( '/'.preg_quote( $prefix, '/' ).'|'.preg_quote( $suffix, '/' ).'/', '', $cKey );
	remark( $cKey.' ('.$cValue.'):' );
	foreach( $constants as $dKey => $dValue ){
		$dKey	= preg_replace( '/'.preg_quote( $prefix, '/' ).'|'.preg_quote( $suffix, '/' ).'/', '', $dKey );
		if( $cKey === $dKey )
			continue;
		if( $cValue < $dValue )
			continue;
		$value	= $cValue & $dValue;
		if( $value !== $dValue )
			continue;
		remark( ' - '.$dKey.' ('.$dValue.') => '.$value );
	}
}
remark();
