<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';
require_once __DIR__.'/DemoMailboxApp.php';

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\UI\HTML\Exception\View;
use CeusMedia\MailDemo\Web\Mailbox\DemoMailboxApp;

/** @var Dictionary $config */

try{
    $app	= new DemoMailboxApp( $config->getAll( 'mailbox_', TRUE ) );
}
catch( Exception $e ){
    View::display( $e );
}
