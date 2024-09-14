<?php
require_once dirname( __DIR__ ).'/_bootstrap.php';
require_once __DIR__.'/DemoMailboxApp.php';

/** @var \CeusMedia\Common\ADT\Collection\Dictionary $config */


$app	= new DemoMailboxApp( $config->getAll( 'mailbox_', TRUE ) );
