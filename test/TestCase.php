<?php
namespace CeusMedia\MailTest;

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\UI\DevOutput;
use CeusMedia\Mail\Address;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;

use Exception;
use RuntimeException;

class TestCase extends PhpUnitTestCase
{
	protected string $pathLibrary;
	protected string $pathTests;
	protected string $configFile;
	protected array $configDefaultKeys	= [
		'server.host',
		'server.port',
		'mailbox.name',
		'mailbox.address',
		'auth.mode',
		'auth.username',
		'auth.password',
		'security.encryption',
		'security.certificate',
		'security.antivirus',
	];

	public function __construct( $name = NULL )
	{
		parent::__construct( $name );
		new DevOutput();
		$this->pathLibrary		= dirname( __DIR__ ).'/';
		$this->pathTests		= __DIR__.'/';
		$this->configFile		= $this->pathLibrary.'Mail.ini';
		if( !file_exists( $this->configFile ) )
			$this->configFile	.= '.dist';
		$iniFileData		= parse_ini_file( $this->configFile, TRUE );
		if( FALSE === $iniFileData )
			throw new RuntimeException( 'Loading library configuration failed' );
		$this->version		= $iniFileData['library']['version'];
		$this->phpVersion	= phpversion();
	}

	//  --  PROTECTED  --  //

	protected function requireReceiverConfig(): Dictionary
	{
		try{
			return $this->getReceiverConfig();
		}
		catch( Exception $e ){
			$this->markTestSkipped( 'Runtime incomplete: '.$e->getMessage() );
		}
	}

	protected function requireSenderConfig(): Dictionary
	{
		try{
			return $this->getSenderConfig();
		}
		catch( Exception $e ){
			$this->markTestSkipped( 'Runtime incomplete: '.$e->getMessage() );
		}
	}

	protected function getAddressIP( $address ): string
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		return gethostbyname( $address->getDomain() );
	}

	protected function getCurrentIP(): string
	{
		return file_get_contents( 'https://ipecho.net/plain' );
	}

	//  --  PRIVATE  --  //

	protected function getReceiverConfig(): Dictionary
	{
		$config	= array();
		foreach( $this->configDefaultKeys as $key )
			$config[$key]	= NULL;
		if( !file_exists( $this->configFile ) )
			throw new RuntimeException( 'Config file "Mail.ini" is missing' );
		$ini	= parse_ini_file( $this->configFile, TRUE );
		if( !isset( $ini['phpunit.receiver'] ) )
			throw new RuntimeException( 'Config file "Mail.ini" is missing section "phpunit.receiver"' );
		foreach( $ini['phpunit.receiver'] as $key => $value )
			if( !preg_match( '/^\{\{.+\}\}$/', $value ) )
				$config[$key]	= $value;
		if( !$config['server.host'] )
			throw new RuntimeException( 'Config file "Mail.ini" is not having settings in section "phpunit.receiver"' );
		return new Dictionary( $config );
	}

	protected function getSenderConfig(): Dictionary
	{
		$config	= array();
		foreach( $this->configDefaultKeys as $key )
			$config[$key]	= NULL;
		if( !file_exists( $this->configFile ) )
			throw new RuntimeException( 'Config file "Mail.ini" is missing' );
		$ini	= parse_ini_file( $this->configFile, TRUE );
		if( !isset( $ini['phpunit.sender'] ) )
			throw new RuntimeException( 'Config file "Mail.ini" is missing section "phpunit.sender"' );
		foreach( $ini['phpunit.sender'] as $key => $value )
			if( !preg_match( '/^\{\{.+\}\}$/', $value ) )
				$config[$key]	= $value;
		if( !$config['server.host'] )
			throw new RuntimeException( 'Config file "Mail.ini" is not having settings in section "phpunit.sender"' );
		return new Dictionary( $config );
	}
}
