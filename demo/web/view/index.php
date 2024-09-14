<?php
namespace CeusMedia\MailDemo\Web\View;

use CeusMedia\Common\FS\Autoloader\Psr4;
use CeusMedia\Common\UI\HTML\Exception\Page as ExceptionPage;
use CeusMedia\Common\Loader;

require_once dirname( __DIR__ ).'/_bootstrap.php';


new \CeusMedia\Common\UI\DevOutput;

try{
	Psr4::getInstance()->addNamespace( 'CeusMedia\MailDemo\Web\View', 'inc' )->register();
	$demo	= new App();
	$demo->run();
}
catch( Exception $e ){
	ExceptionPage::display( $e );
}
