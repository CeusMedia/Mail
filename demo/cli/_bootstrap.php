<?php

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\UI\DevOutput;

(php_sapi_name() == 'cli' ) or die('Access denied: CLI only.');
(@include dirname( dirname( __DIR__ ) ).'/vendor/autoload.php') or die('Please use composer to install required packages.');
new DevOutput;

error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );

$configFile	= dirname( __DIR__ ).'/config.ini';
if( !file_exists( $configFile ) )
	die( 'Please copy "config.ini.dist" to "config.ini" and configure it.' );
$config		= new Dictionary();
foreach( parse_ini_file( $configFile, TRUE ) as $section => $values )
	foreach( $values as $key => $value )
		$config->set( $section.'_'.$key, $value );

$files	= [];
foreach( new \DirectoryIterator( __DIR__.'/../mails' ) as $entry ){
	if( $entry->isDir() || $entry->isDot() )
		continue;
	$files[$entry->getPathname()]	= $entry->getFilename();
}
ksort( $files );
