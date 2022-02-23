<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';

use UI_HTML_Exception_Page as ExceptionPage;
new UI_DevOutput;

try{
	Loader::registerNew( 'php', NULL, 'inc' );
	$demo	= new App();
	$demo->run();
}
catch( Exception $e ){
	ExceptionPage::display( $e );
}
