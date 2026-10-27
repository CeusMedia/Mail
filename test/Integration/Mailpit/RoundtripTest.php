<?php
/**
 *	Integration test for rendering, sending and parsing messages: what is sent must arrive unchanged.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Integration_Mailpit
 */

namespace CeusMedia\MailTest\Integration\Mailpit;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Parser;

/**
 *	Each message is checked twice: as decoded by Mailpit (independent parser)
 *	and as parsed again by this library from the received source.
 *
 *	@coversDefaultClass	\CeusMedia\Mail\Message\Renderer
 */
class RoundtripTest extends MailpitTestCase
{
	/** 1x1 pixel, transparent */
	private const PNG	= 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

	private ?string $tempPath	= NULL;

	protected function tearDown(): void
	{
		if( NULL !== $this->tempPath ){
			array_map( 'unlink', glob( $this->tempPath.'*' ) ?: [] );
			rmdir( $this->tempPath );
		}
		parent::tearDown();
	}

	public static function encodingProvider(): array
	{
		return [
			'base64'			=> ['base64'],
			'quoted-printable'	=> ['quoted-printable'],
			'8bit'				=> ['8bit'],
		];
	}

	/**
	 *	UTF-8 text, also with a line longer than the line length for wrapping, but below the SMTP limit of 998.
	 *	@dataProvider	encodingProvider
	 *	@covers			::render
	 */
	public function testTextRoundtrip( string $encoding ): void
	{
		$text		= "Grüße aus Köln!\r\n".trim( str_repeat( 'Größenänderung ', 10 ) )."\r\nEnde";
		$message	= $this->createMessage()->addText( $text, 'UTF-8', $encoding );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertSame( $this->normalize( $text ), $this->normalize( $received['Text'] ), 'decoded by Mailpit' );
		self::assertSame( $this->normalize( $text ), $this->normalize( $parsed->getText()->getContent() ), 'parsed by library' );
	}

	/**
	 *	SMTP ends data by a line with a single dot, so lines starting with a dot must be doubled on sending (RFC 5321, 4.5.2).
	 *	@dataProvider	encodingProvider
	 *	@covers			\CeusMedia\Mail\Transport\SMTP::send
	 */
	public function testLinesStartingWithDot( string $encoding ): void
	{
		$text		= "Erste Zeile\r\n.zweite Zeile\r\n..dritte Zeile\r\n.\r\nletzte Zeile";
		$message	= $this->createMessage()->addText( $text, 'UTF-8', $encoding );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertSame( $this->normalize( $text ), $this->normalize( $received['Text'] ), 'decoded by Mailpit' );
		self::assertSame( $this->normalize( $text ), $this->normalize( $parsed->getText()->getContent() ), 'parsed by library' );
	}

	/**
	 *	@covers		::render
	 */
	public function testEncodedSubjectAndNames(): void
	{
		$subject	= 'Größenänderung für Übergrößen – ein ziemlich langer Betreff, der gefaltet werden muss';
		$message	= Message::getInstance()
			->setSender( 'sender@example.org', 'Jürgen Müller' )
			->addRecipient( 'receiver@example.org', 'Zoë Ærøskøbing' )
			->setSubject( $subject.' ['.uniqid( 'mp', TRUE ).']' )
			->addText( 'Hallo' );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertSame( $message->getSubject(), $received['Subject'] );
		self::assertSame( 'Jürgen Müller', $received['From']['Name'] );
		self::assertSame( 'Zoë Ærøskøbing', $received['To'][0]['Name'] );

		self::assertSame( $message->getSubject(), $parsed->getSubject() );
		self::assertSame( 'Jürgen Müller', $parsed->getSender()?->getName() );

		//  headers are sent ASCII only, names encoded (RFC 2047)
		$raw		= $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] );
		$headers	= (string) strstr( $raw, "\r\n\r\n", TRUE );
		self::assertMatchesRegularExpression( '/^From: =\?UTF-8\?B\?.+\?= <sender@example\.org>\r?$/m', $headers );
		self::assertMatchesRegularExpression( '/^To: =\?UTF-8\?B\?.+\?= <receiver@example\.org>\r?$/m', $headers );
		self::assertTrue( mb_check_encoding( $headers, 'ASCII' ), 'headers without 8bit characters' );
	}

	/**
	 *	Names with comma and non-ASCII characters are encoded as a whole and parsed back as one address.
	 *	@covers		::render
	 */
	public function testEncodedNamesWithComma(): void
	{
		$message	= $this->createMessage()
			->addRecipient( 'second@example.org', 'Doe, Jürgen' )
			->addRecipient( 'copy@example.org', 'Müller; Max', 'cc' )
			->addText( 'Hallo' );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertSame( ['receiver@example.org', 'second@example.org'], array_column( $received['To'], 'Address' ) );
		self::assertSame( 'Doe, Jürgen', $received['To'][1]['Name'] );
		self::assertSame( 'Müller; Max', $received['Cc'][0]['Name'] );

		$to		= $parsed->getRecipientsByType( 'to' )->getAll();
		self::assertCount( 2, $to );
		self::assertSame( 'Doe, Jürgen', $to[1]->getName( FALSE ) );
		$cc		= $parsed->getRecipientsByType( 'cc' )->getAll();
		self::assertCount( 1, $cc );
		self::assertSame( 'Müller; Max', $cc[0]->getName( FALSE ) );
	}

	/**
	 *	@covers		::render
	 */
	public function testTextAndHtmlAlternative(): void
	{
		$html		= '<html><body><p>Grüße <b>fett</b></p></body></html>';
		$message	= $this->createMessage()
			->addText( 'Grüße fett' )
			->addHTML( $html );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertSame( 'Grüße fett', $this->normalize( $received['Text'] ) );
		self::assertSame( $html, $this->normalize( $received['HTML'] ) );
		self::assertSame( 'Grüße fett', $this->normalize( $parsed->getText()->getContent() ) );
		self::assertSame( $html, $this->normalize( $parsed->getHTML()->getContent() ) );
	}

	/**
	 *	Binary attachment, with all byte values and an SMTP end-of-data sequence, must arrive byte by byte.
	 *	@covers		::render
	 */
	public function testBinaryAttachment(): void
	{
		$content	= implode( '', array_map( 'chr', range( 0, 255 ) ) )."\r\n.\r\n".random_bytes( 8192 );
		$filePath	= $this->createTempFile( 'Prüfbericht 2026.bin', $content );
		$message	= $this->createMessage()
			->addText( 'Anhang anbei' )
			->addAttachment( $filePath, 'application/octet-stream', NULL, 'Prüfbericht 2026.bin' );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertCount( 1, $received['Attachments'] );
		$attachment	= $received['Attachments'][0];
		self::assertSame( 'Prüfbericht 2026.bin', $attachment['FileName'] );
		self::assertSame( 'application/octet-stream', $attachment['ContentType'] );
		self::assertSame( $content, $this->fetchPart( self::INSTANCE_PLAIN, $received['ID'], $attachment['PartID'] ) );

		$attachments	= $parsed->getAttachments();
		self::assertCount( 1, $attachments );
		self::assertSame( 'Prüfbericht 2026.bin', $attachments[0]->getFileName() );
		self::assertSame( $content, $attachments[0]->getContent() );
	}

	/**
	 *	File names with quotes, semicolon, umlauts and long ones arrive unchanged, at Mailpit and own parser.
	 *	@covers		::render
	 */
	public function testAttachmentFileNames(): void
	{
		$fileNames	= [
			'a "b"; c.txt',
			'Grüße aus Köln.txt',
			str_repeat( 'Größenänderung ', 6 ).'– Übersicht.pdf',
		];
		$message	= $this->createMessage()->addText( 'Anhänge anbei' );
		foreach( $fileNames as $nr => $fileName )
			$message->addAttachment( $this->createTempFile( 'file'.$nr.'.txt', 'Inhalt '.$nr ), 'text/plain', NULL, $fileName );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertSame( $fileNames, array_column( $received['Attachments'], 'FileName' ) );
		self::assertSame( $fileNames, array_map( static fn( $part ) => $part->getFileName(), $parsed->getAttachments() ) );

		$raw		= $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] );
		self::assertTrue( mb_check_encoding( (string) preg_replace( '/^Content-Transfer-Encoding: base64\r\n\r\n.*?(?=\r\n--)/ms', '', $raw ), 'ASCII' ) );
	}

	/**
	 *	Inline image, referenced by content ID from HTML.
	 *	@covers		::render
	 */
	public function testInlineImage(): void
	{
		$filePath	= $this->createTempFile( 'logo.png', (string) base64_decode( self::PNG ) );
		$message	= $this->createMessage()
			->addText( 'Logo' )
			->addHTML( '<p><img src="cid:logo"/></p>' )
			->addInlineImage( 'logo', $filePath, 'image/png' );
		[$received, $parsed]	= $this->sendAndReceive( $message );

		self::assertCount( 0, $received['Attachments'] );
		self::assertCount( 1, $received['Inline'] );
		$inline	= $received['Inline'][0];
		self::assertSame( 'logo', $inline['ContentID'] );
		self::assertSame( 'image/png', $inline['ContentType'] );
		self::assertSame( base64_decode( self::PNG ), $this->fetchPart( self::INSTANCE_PLAIN, $received['ID'], $inline['PartID'] ) );

		$images	= $parsed->getInlineImages();
		self::assertCount( 1, $images );
		self::assertSame( 'logo', $images[0]->getId() );
	}

	//  --  PRIVATE  --  //

	private function createTempFile( string $fileName, string $content ): string
	{
		if( NULL === $this->tempPath ){
			$this->tempPath	= sys_get_temp_dir().'/ceusmedia-mail-test-'.uniqid().'/';
			mkdir( $this->tempPath );
		}
		file_put_contents( $this->tempPath.$fileName, $content );
		return $this->tempPath.$fileName;
	}

	/**
	 *	Ignores line break style and the line break before the end of data.
	 */
	private function normalize( ?string $content ): string
	{
		return rtrim( str_replace( "\r\n", "\n", (string) $content ), "\n" );
	}

	/**
	 *	@return		array{0:array<string,mixed>,1:Message}		Message as decoded by Mailpit and as parsed by library
	 */
	private function sendAndReceive( Message $message ): array
	{
		$this->createTransport( self::INSTANCE_PLAIN, FALSE )->send( $message );
		$received	= $this->fetchMessage( self::INSTANCE_PLAIN, $message );
		$parsed		= Parser::getInstance()->parse( $this->fetchRaw( self::INSTANCE_PLAIN, $received['ID'] ) );
		return [$received, $parsed];
	}
}
