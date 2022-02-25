<?php

use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection as MailboxConnection;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP as Transport;
use FS_File_INI_SectionReader as ConfigReader;
use CLI_Question as Question;

class MailClient
{
	protected $configFileName	= '.config';
	protected $listLimit		= 20;
	protected $listOffset		= 0;
	protected $mailbox;
	protected $transport;
	protected $passwordImap;
	protected $passwordSmtp;

	public function __construct()
	{
		$this->output	= new CLI_Output();
		$this->color	= new CLI_Color();
	}

	public function __destruct()
	{
//		$this->clearScreen();
	}

	public function run()
	{
		if( !file_exists( $this->configFileName ) ){
			$this->output->newLine( $this->color->asInfo( 'No configuration saved yet' ) );
			$this->output->newLine();
			$this->createConfig();
		}
		$this->config	= new ConfigReader( $this->configFileName );

		if( $this->config->hasProperty( 'IMAP', 'password' ) )
			$this->passwordImap	= $this->config->getProperty( 'IMAP', 'password' );
		if( !$this->passwordImap )
			$this->passwordImap	= Question::getInstance( 'Passwort' )->setBreak( FALSE )->ask();

		$this->passwordSmtp	= $this->passwordImap;
		if( $this->config->hasProperty( 'SMTP', 'password' ) )
			$this->passwordSmtp	= $this->config->getProperty( 'SMTP', 'password' );

		$this->mailbox	= Mailbox::getInstance(MailboxConnection::getInstance(
			$this->config->getProperty( 'IMAP', 'host' ),
			$this->config->getProperty( 'IMAP', 'username' ),
			$this->passwordImap,
			$this->config->getProperty( 'IMAP', 'port' ) == 993
		));
		$this->transport	= Transport::getInstance(
			$this->config->getProperty( 'SMTP', 'host' ),
			$this->config->getProperty( 'SMTP', 'port' ),
			$this->config->getProperty( 'SMTP', 'username' ),
			$this->passwordSmtp
		);
		$this->showMainMenu();
	}

	public function setConfigFile( string $configFile ): self
	{
		$this->configFileName	= $configFile;
		return $this;
	}


	protected function clearScreen()
	{
		system( "clear" );
//		$this->createMail();
//		$this->indexMails();
	}

	protected function indexMails()
	{
		$this->clearScreen();
		$search		= MailboxSearch::getInstance()
			->setOrder()
			->setLimit( $this->listLimit )
			->setOffset( $this->listOffset );
		$collection	= $this->mailbox->performSearch( $search );
//		print_m( $collection );
		print( "  Account: ".$this->config->getProperty( 'IMAP', 'username' ) );
		$this->output->newLine( str_repeat( '-', 80 ).PHP_EOL );
		print( "Loading mails ..." );
		$lines	= array();
		foreach( $collection as $mail ){
			try{
				$message	= $mail->getMessage();
				$sender		= $message->getRecipientsByType( 'TO' )->current()->getAddress();
				$sender		= Alg_Text_Trimmer::trim( $sender, 40 );
				$subject	= Alg_Text_Trimmer::trim( $message->getSubject(), 40 );
				$lines[]	= join( '  ', array(
					str_pad( $mail->getId(), 6, ' ', STR_PAD_LEFT ),
					str_pad( $sender, 40, ' ' ),
					str_pad( $subject, 40, ' ' ),
				) );
			}
			catch( Exception $e ){
				$lines[]	= join( '  ', array(
					str_pad( $mail->getId(), 6, ' ', STR_PAD_LEFT ),
					str_pad( $sender, 40, ' ' ),
					"Error: ".$e->getMessage(),
				) );
			}
		}
		print( "\r".join( PHP_EOL, $lines ) );
		$action	= $this->askForAction( array( 'exit', 'next', 'prev', 'read', 'delete', 'create' ) );
		switch( $action ){
			case 'next':
				$this->listOffset = $this->listOffset + $this->listLimit;
				$this->indexMails();
				break;
			case 'prev':
				$this->listOffset = max( 0, $this->listOffset - $this->listLimit );
				$this->indexMails();
				break;
			case 'read':
				$this->readMail( $this->askMailId() );
				break;
			case 'delete':
				$this->deleteMail( $this->askMailId() );
				break;
			case 'create':
				$this->createMail();
				break;
		}
	}

	protected function askForAction( $options, $default = NULL )
	{
		$this->output->newLine( str_repeat( '-', 80 ).PHP_EOL );
		if( is_null( $default ) )
			$default	= current( $options );
		return Question::getInstance( 'Aktion' )
			->setDefault( $default )
			->setOptions( $options )
			->setBreak( FALSE )
			->ask();
	}

	protected function askMailId()
	{
		return Question::getInstance( 'Mail-ID' )
			->setType( Question::TYPE_INTEGER )
			->setBreak( FALSE )
			->ask();
	}

	protected function readMail( $mailId )
	{
		$this->clearScreen();
		try{
			$message	= $this->mailbox->getMailAsMessage( $mailId );
			$sender		= $message->getSender()->get();
			$subject	= $message->getSubject();
			if( $message->hasText() ){
				$body	= $message->getText()->getContent();
			} else if( $message->hasHTML() ){
	//			$body	= strip_tags( $message->getHTML()->getContent() );
				$body	= HtmlToPlainText::convert( $message->getHTML()->getContent() );
			}
		}
		catch( Exception $e ){
			$sender		= 'unbekannt';
			$subject	= 'unbekannt';
			$body		= $e->getMessage();
		}
		print( "  Absender: ".$sender." | Betreff: ".$subject );
		$this->output->newLine( str_repeat( '-', 80 ).PHP_EOL );
		print( $body.PHP_EOL );
		$action	= $this->askForAction( array( 'list', 'reply', 'delete' ) );
		switch( $action ){
			case 'list':
				$this->indexMails();
				break;
			case 'reply':
				$this->replyMail( $mailId );
				break;
			case 'delete':
				$this->deleteMail( $mailId );
				break;
		}
	}

	protected function showMainMenu()
	{
		$this->indexMails();
//		$this->clearScreen();
//		print_m( $this->config->getProperties() );
//		$decision	= new CLI_Decision();
	}

	protected function createMail()
	{
		$this->clearScreen();
		$receiver	= Question::getInstance( 'Empfänger' )
			->setDefault( $this->config->getProperty( 'IMAP', 'username' ) )
			->setBreak( FALSE )->ask();
		$subject	= Question::getInstance( 'Betreff' )
			->setDefault( 'Test '.date( 'r' ) )
			->setBreak( FALSE )->ask();

		print( 'Enter mail body and hit CTRL-D to end input:'.PHP_EOL.PHP_EOL );
		$text	= stream_get_contents( fopen( 'php://stdin', 'r' ) );

		$question	= new CLI_Question( 'Mail versenden?', CLI_Question::TYPE_BOOLEAN, 'y' );
		$decision	= $question->setBreak( FALSE )->ask();
		if( $decision === 'y' ){
			$message	= Message::create()
				->setSender( $this->config->getProperty( 'IMAP', 'username' ) )
				->addRecipient( $receiver )
				->setSubject( $subject )
				->addText( $text );
			$this->transport->send( $message );
			$this->output->newLine( $this->color->asSuccess( 'Mail sent.' ) );
			$this->output->newLine();
		}

		$action	= $this->askForAction( array( 'list', 'create' ) );
		switch( $action ){
			case 'list':
				$this->indexMails();
				break;
			case 'create':
				$this->createMail();
				break;
		}
	}

	protected function createConfig()
	{
		$regexDefault	= '/^\{\{(\S+)\.(\S+)\}\}$/';
		$pairs			= array(
			'Account'	=> array(
				'address'	=> array(
					'label'		=> 'E-Mail-Adresse',
				),
				'firstname'	=> array(
					'label'		=> 'Vorname',
				),
				'surname'	=> array(
					'label'		=> 'Nachname',
				),
			),
			'IMAP'	=> array(
				'host'		=> array(
					'label'		=> 'IMAP-Server',
					'default'	=> 'mail.domain.tld',
				),
				'port'		=> array(
					'label'		=> 'Port',
					'default'	=> 993,
					'options'	=> array( 993, 143 ),
					'type'		=> Question::TYPE_INTEGER,
				),
				'username'	=> array(
					'label'		=> 'Benutzer',
					'mandatory'	=> '{{Account.address}}',
				),
			),
			'SMTP'	=> array(
				'host'		=> array(
					'label'		=> 'SMTP-Server',
					'default'	=> '{{IMAP.host}}',
				),
				'port'		=> array(
					'label'		=> 'Port',
					'default'	=> 587,
					'options'	=> array( 587, 465, 25 ),
					'type'		=> Question::TYPE_INTEGER,
				),
				'username'	=> array(
					'label'		=> 'Benutzer',
					'mandatory'	=> TRUE,
					'default'	=> '{{IMAP.username}}'
				),
			),
		);
		$data	= array();
		foreach( $pairs as $sectionKey => $sectionData ){
			foreach( $sectionData as $questionKey => $questionData ){
				$question	= new Question( $questionData['label'] );
				if( !empty( $questionData['type'] ) )
					$question->setType( $questionData['type'] );
				if( !empty( $questionData['default'] ) ){
					$default	= $questionData['default'];
					if( preg_match( $regexDefault, $default ) ){
						$matches	= array();
						preg_match_all( $regexDefault, $default, $matches );
						$default	= $data[$matches[1][0]][$matches[2][0]];
					}
					$question->setDefault( $default );
				}
				if( !empty( $questionData['options'] ) )
					$question->setOptions( $questionData['options'] );
	//			if( !empty( $questionData['options'] ) )
	//				$question->setOptions( $questionData['options'] );
				$question->setBreak( FALSE );
				$data[$sectionKey][$questionKey]	= $question->ask();
			}
		}
	//	print_m( $data );
		$file	= new FS_File_INI_Creator( TRUE );
		foreach( $data as $section => $pairs ){
			$file->addSection( $section );
			foreach( $pairs as $key => $value ){
				$file->addPropertyToSection( $key, $value, $section );
			}
		}
		$file->write( $this->configFileName );
	}
}
