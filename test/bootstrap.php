<?php
if( !@include_once dirname( __DIR__ ).'/vendor/autoload.php' ){
	$path = dirname(__DIR__) . '/src/';
	require_once $path . 'Message.php';
	require_once $path . 'Check/Address.php';
	require_once $path . 'Check/Recipient.php';
	require_once $path . 'Header/Field.php';
	require_once $path . 'Header/Section.php';
	require_once $path . 'Part.php';
	require_once $path . 'Part/Attachment.php';
	require_once $path . 'Part/Text.php';
	require_once $path . 'Part/HTML.php';
	require_once $path . 'Participant.php';
	require_once $path . 'Parser.php';
	require_once $path . 'Renderer.php';
}

//class Test_Case extends PHPUnit_Framework_TestCase{}

