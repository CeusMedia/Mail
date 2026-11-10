<?php
namespace CeusMedia\MailTest\Transport\SMTP\Auth;

use CeusMedia\Mail\Transport\SMTP\SmtpConnection;
use CeusMedia\Mail\Transport\SMTP\Response;

/**
 *	Mock implementation of SmtpConnection for testing AUTH strategies.
 */
class SmtpConnectionMock implements SmtpConnection
{
	/** @var array<string>  $sentCommands  Recorded commands sent via sendChunk */
	public array $sentCommands = [];

	/** @var array<string|null>  $verboseMessages  Recorded verbose messages from sendChunk */
	public array $verboseMessages = [];

	/** @var array<Response>  $responses  Queue of responses to return */
	private array $responses;

	/** @var int  $responseIndex  Current position in responses queue */
	private int $responseIndex = 0;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array<Response>	$responses	Responses to return in order
	 */
	public function __construct( array $responses = [] )
	{
		$this->responses = $responses;
	}

	public function sendChunk( string $command, ?string $verboseMessage = NULL ): bool
	{
		$this->sentCommands[] = $command;
		$this->verboseMessages[] = $verboseMessage;
		return TRUE;
	}

	public function readResponse(): Response
	{
		if ( $this->responseIndex >= count( $this->responses ) ) {
			return new Response( 500, 'Unexpected readResponse call' );
		}
		return $this->responses[ $this->responseIndex++ ];
	}

	public function checkResponse( array $acceptedCodes ): \CeusMedia\Mail\Transport\SMTP\Response
	{
		$response = $this->readResponse();
		if ( !in_array( $response->getCode(), $acceptedCodes, TRUE ) ) {
			throw new \RuntimeException(
				'Expected response code in [' . implode(', ', $acceptedCodes) . '], got ' . $response->getCode()
			);
		}
		return $response;
	}

	public function printResponse( Response $response ): void
	{
		//  No-op in mock — tests don't need verbose output
	}
}


