<?php
namespace CeusMedia\MailDemo\CLI\Client;

use CeusMedia\Common\Alg\Text\Trimmer as TextTrimmer;
use CeusMedia\Common\FS\File\INI\Creator as ConfigCreator;
use CeusMedia\Common\FS\File\INI\SectionReader as ConfigReader;
use CeusMedia\Common\CLI\Color as CliColor;
use CeusMedia\Common\CLI\Output as CliOutput;
use CeusMedia\Common\CLI\Question as Question;
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection as MailboxConnection;
use CeusMedia\Mail\Mailbox\Search as MailboxSearch;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP as Transport;

class App
{
	protected string $configFileName	= '.config';
	protected int $listLimit		= 20;
	protected int $listOffset		= 0;
	protected Mailbox $mailbox;
	protected Transport $transport;
	protected string $passwordImap;
	protected string $passwordSmtp;

	protected ConfigReader $config;
	protected CliOutput $output;
	protected CliColor $color;

	public function __construct()
	{
		$this->output	= new CliOutput();
		$this->color	= new CliColor();
	}

	public function __destruct()
	{
//		$this->clearScreen();
	}

	public function run(): void
	{
		if( !file_exists( $this->configFileName ) ){
			$this->output->newLine( $this->color->asInfo( 'No configuration saved yet' ) );
			$this->output->newLine();
			$this->createConfig();
		}
		$this->config	= new ConfigReader( $this->configFileName );

		if( $this->config->hasProperty( 'IMAP', 'password' ) )
			$this->passwordImap	= $this->config->getProperty( 'IMAP', 'password' );
		else
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
			(int) $this->config->getProperty( 'SMTP', 'port' ),
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


	protected function clearScreen(): void
	{
		system( "clear" );
//		$this->createMail();
//		$this->indexMails();
	}

	protected function indexMails(): void
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
				$sender		= TextTrimmer::trim( $sender, 40 );
				$subject	= TextTrimmer::trim( $message->getSubject(), 40 );
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

	protected function askForAction( array $options, $default = NULL ): string
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

	protected function askMailId(): string
	{
		return Question::getInstance( 'Mail-ID' )
			->setType( Question::TYPE_INTEGER )
			->setBreak( FALSE )
			->ask();
	}

	protected function readMail( int $mailId ): void
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

	protected function showMainMenu(): void
	{
		$this->indexMails();
//		$this->clearScreen();
//		print_m( $this->config->getProperties() );
//		$decision	= new CLI_Decision();
	}

	protected function createMail(): void
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

		$question	= new Question( 'Mail versenden?', Question::TYPE_BOOLEAN, 'y' );
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

	protected function createConfig(): void
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
		$file	= new ConfigCreator( TRUE );
		foreach( $data as $section => $pairs ){
			$file->addSection( $section );
			foreach( $pairs as $key => $value ){
				$file->addPropertyToSection( $key, $value, $section );
			}
		}
		$file->write( $this->configFileName );
	}
}
