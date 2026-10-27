<?php
/**
 *	Base for integration tests against local Mailpit instances.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Mailpit
 */

namespace CeusMedia\MailTest\Integration\Mailpit;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;
use CeusMedia\MailTest\TestCase;
use RuntimeException;

/**
 *	Talks to the Mailpit instances of util/mailpit/docker-compose.yml:
 *	sends by SMTP, then reads the received message by Mailpit's REST API.
 *	Tests are skipped if the instance is not running (start with: util/mailpit/mailpit.sh up).
 *
 *	Messages are found by a unique subject, never deleted in bulk, so tests may run in parallel.
 *
 *	Environment: MAILPIT_HOST (default: localhost), MAILPIT_{PLAIN|STARTTLS|TLS}_{SMTP|API} for ports.
 */
abstract class MailpitTestCase extends TestCase
{
	protected const INSTANCE_PLAIN		= 'plain';
	protected const INSTANCE_STARTTLS	= 'starttls';
	protected const INSTANCE_TLS		= 'tls';

	protected const USERNAME			= 'tester';
	protected const PASSWORD			= 'secret';

	/** @var array<string,array{smtp:int,api:int}> */
	private const PORTS	= [
		self::INSTANCE_PLAIN	=> ['smtp' => 1525, 'api' => 8525],
		self::INSTANCE_STARTTLS	=> ['smtp' => 1587, 'api' => 8587],
		self::INSTANCE_TLS		=> ['smtp' => 1465, 'api' => 8465],
	];

	//  --  PROTECTED  --  //

	/**
	 *	Returns SMTP transport for an instance, skips test if instance is not running.
	 *	Encryption is set by instance, not detected by port (test ports differ from 465/587).
	 *	@param		string		$instance			One of INSTANCE_*
	 *	@param		bool		$withAuth			Flag: login with valid credentials
	 *	@param		bool		$trustCertificate	Flag: trust self-signed certificate of test instances
	 */
	protected function createTransport( string $instance, bool $withAuth = TRUE, bool $trustCertificate = TRUE ): SMTP
	{
		$this->requireInstance( $instance );
		$transport	= new SMTP( $this->getHost() );
		$transport->setPort( $this->getPort( $instance, 'smtp' ), FALSE );
		$transport->setHeloHost( 'client.test' );
		$transport->setSecure( self::INSTANCE_PLAIN !== $instance );
		$transport->setImplicitTls( self::INSTANCE_TLS === $instance );
		if( $trustCertificate && self::INSTANCE_PLAIN !== $instance )
			$transport->setSslOptions( ['cafile' => $this->getCertificateFile()] );
		if( $withAuth )
			$transport->setAuth( self::USERNAME, self::PASSWORD );
		return $transport;
	}

	/**
	 *	Returns new message with unique subject, to find it in Mailpit again.
	 */
	protected function createMessage( string $subject = 'Test' ): Message
	{
		return Message::getInstance()
			->setSender( 'sender@example.org' )
			->addRecipient( 'receiver@example.org' )
			->setSubject( $subject.' ['.uniqid( 'mp', TRUE ).']' );
	}

	/**
	 *	Returns message details, as received by Mailpit.
	 *	Waits shortly, since Mailpit stores the message asynchronously.
	 *	@return		array<string,mixed>
	 */
	protected function fetchMessage( string $instance, Message $message ): array
	{
		return $this->fetchMessageBySubject( $instance, (string) $message->getSubject() );
	}

	/**
	 *	Returns message details by unique subject, as received by Mailpit.
	 *	@return		array<string,mixed>
	 */
	protected function fetchMessageBySubject( string $instance, string $subject ): array
	{
		$query		= 'subject:"'.str_replace( '"', '', $subject ).'"';
		for( $try = 0; $try < 50; $try++ ){
			$result	= $this->requestApi( $instance, '/api/v1/search?query='.rawurlencode( $query ) );
			foreach( $result['messages'] as $summary )
				if( $summary['Subject'] === $subject )
					return $this->requestApi( $instance, '/api/v1/message/'.$summary['ID'] ) + $summary;
			usleep( 100_000 );
		}
		self::fail( 'Message "'.$subject.'" not received by Mailpit' );
	}

	/**
	 *	Returns part of received message, e.g. an attachment, as raw bytes.
	 */
	protected function fetchPart( string $instance, string $messageId, string $partId ): string
	{
		return $this->requestApi( $instance, '/api/v1/message/'.$messageId.'/part/'.$partId, FALSE );
	}

	/**
	 *	Returns received message in source form.
	 */
	protected function fetchRaw( string $instance, string $messageId ): string
	{
		return $this->requestApi( $instance, '/api/v1/message/'.$messageId.'/raw', FALSE );
	}

	protected function getHost(): string
	{
		$host	= getenv( 'MAILPIT_HOST' );
		return FALSE !== $host && '' !== $host ? $host : 'localhost';
	}

	protected function getPort( string $instance, string $service ): int
	{
		$port	= getenv( 'MAILPIT_'.strtoupper( $instance.'_'.$service ) );
		return FALSE !== $port && '' !== $port ? (int) $port : self::PORTS[$instance][$service];
	}

	protected function requireInstance( string $instance ): void
	{
		$url		= 'http://'.$this->getHost().':'.$this->getPort( $instance, 'api' ).'/api/v1/info';
		$context	= stream_context_create( ['http' => ['timeout' => 2]] );
		if( FALSE === @file_get_contents( $url, FALSE, $context ) )
			self::markTestSkipped( 'Mailpit instance "'.$instance.'" not running, start with: util/mailpit/mailpit.sh up' );
	}

	/**
	 *	Returns path of self-signed certificate of the test instances, skips test if missing.
	 */
	protected function getCertificateFile(): string
	{
		$certFile	= $this->pathLibrary.'util/mailpit/tls/cert.pem';
		if( !file_exists( $certFile ) )
			self::markTestSkipped( 'Test certificate missing, create with: util/mailpit/mailpit.sh up' );
		return $certFile;
	}

	/**
	 *	@return		array<string,mixed>|string		Decoded JSON or raw body
	 */
	protected function requestApi( string $instance, string $path, bool $json = TRUE ): array|string
	{
		$url		= 'http://'.$this->getHost().':'.$this->getPort( $instance, 'api' ).$path;
		$context	= stream_context_create( ['http' => ['timeout' => 5]] );
		$body		= @file_get_contents( $url, FALSE, $context );
		if( FALSE === $body )
			throw new RuntimeException( 'Mailpit API request failed: '.$url );
		return $json ? json_decode( $body, TRUE, 512, JSON_THROW_ON_ERROR ) : $body;
	}
}
