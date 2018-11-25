<?php
(php_sapi_name() == 'cli' ) or die('Access denied: CLI only.');
(@include dirname( dirname( __DIR__ ) ).'/vendor/autoload.php') or die('Please use composer to install required packages.');

error_reporting( E_ALL );

if( !file_exists( __DIR__.'/config.ini' ) )
	die( 'Please copy "config.ini.dist" to "config.ini" and configure it.' );
$config		= new ADT_List_Dictionary();
foreach( parse_ini_file( __DIR__.'/config.ini', TRUE ) as $section => $values )
	foreach( $values as $key => $value )
		$config->set( $section.'_'.$key, $value );
