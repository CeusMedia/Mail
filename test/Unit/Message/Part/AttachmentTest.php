<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Part;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part;
use CeusMedia\Mail\Message\Part\Attachment;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\Attachment
 */
class AttachmentTest extends TestCase
{
	protected $delimiter;
	protected $lineLength;

	public function setup(): void
	{
		$this->delimiter	= Message::$delimiter;
		$this->lineLength	= Message::$lineLength;
	}

	/**
	 *	@covers		::__construct
	 */
	public function testConstruct()
	{
		$part	= new Attachment();
		self::assertEquals( 'fixed', $part->getFormat() );
		self::assertEquals( 'base64', $part->getEncoding() );
		self::assertEquals( 'application/octet-stream', $part->getMimeType() );
	}

	/**
	 *	@covers		::getFileATime
	 *	@covers		::setFileATime
	 */
	public function testFileATime()
	{
		$time	= time() - 30;
		$part	= new Attachment();
		$part->setFileATime( $time );
		self::assertEquals( $time, $part->getFileATime() );
	}

	/**
	 *	@covers		::getFileCTime
	 *	@covers		::setFileCTime
	 */
	public function testFileCTime()
	{
		$time	= time() - 30;
		$part	= new Attachment();
		$part->setFileCTime( $time );
		self::assertEquals( $time, $part->getFileCTime() );
	}

	/**
	 *	@covers		::getFileMTime
	 *	@covers		::setFileMTime
	 */
	public function testFileMTime()
	{
		$time	= time() - 30;
		$part	= new Attachment();
		$part->setFileMTime( $time );
		self::assertEquals( $time, $part->getFileMTime() );
	}

	/**
	 *	@covers		::getFileSize
	 *	@covers		::setFileSize
	 */
	public function testFileSize()
	{
		$size	= 1234;
		$part	= new Attachment();
		$part->setFileSize( $size );
		self::assertEquals( $size, $part->getFileSize() );
	}

	/**
	 *	@covers		::getContent
	 *	@covers		::setContent
	 */
	public function testContent()
	{
		$content	= "This is the content. It contains umlauts: äöü.";
		$part		= new Attachment();

		$part->setContent( $content );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( 'application/octet-stream', $part->getMimeType() );
		self::assertEquals( 'base64', $part->getEncoding() );

		$part->setContent( $content );
		$part->setMimeType( 'x-zip' );
		$part->setEncoding( 'binary' );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( 'x-zip', $part->getMimeType() );
		self::assertEquals( 'binary', $part->getEncoding() );
	}

	/**
	 *	@covers		::setFile
	 */
	public function testFile()
	{
		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part		= new Attachment();
		$part->setFile( $file );
		self::assertEquals( basename( __FILE__ ), $part->getFileName() );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( $fileATime, $part->getFileATime() );
		self::assertEquals( $fileCTime, $part->getFileCTime() );
		self::assertEquals( $fileMTime, $part->getFileMTime() );
		self::assertEquals( $fileSize, $part->getFileSize() );

		$part->setFile( $file, 'x-zip', 'binary' );
		self::assertEquals( basename( __FILE__ ), $part->getFileName() );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( 'x-zip', $part->getMimeType() );
		self::assertEquals( 'binary', $part->getEncoding() );
	}

	/**
	 *	@covers		::setFile
	 */
	public function testSetFileException()
	{
		$this->expectException( 'InvalidArgumentException' );
		$part	= new Attachment();
		$part->setFile( 'invalid' );
	}

	/**
	*	@covers		::getFileName
	 *	@covers		::setFileName
	 */
	public function testFileName()
	{
		$part	= new Attachment();
		self::assertEquals( NULL, $part->getFileName() );
		$part->setFileName( "test.file" );
		self::assertEquals( "test.file", $part->getFileName() );
		$part->setFileName( "path/test.file" );
		self::assertEquals( "test.file", $part->getFileName() );

		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part->setFile( $file );
		self::assertEquals( basename( __FILE__ ), $part->getFileName() );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( $fileATime, $part->getFileATime() );
		self::assertEquals( $fileCTime, $part->getFileCTime() );
		self::assertEquals( $fileMTime, $part->getFileMTime() );
		self::assertEquals( $fileSize, $part->getFileSize() );

		$part->setFileName( "test.file" );
		self::assertEquals( "test.file", $part->getFileName() );
		self::assertEquals( $content, $part->getContent() );
		self::assertEquals( $fileATime, $part->getFileATime() );
		self::assertEquals( $fileCTime, $part->getFileCTime() );
		self::assertEquals( $fileMTime, $part->getFileMTime() );
		self::assertEquals( $fileSize, $part->getFileSize() );
	}

	/**
	 *	@covers		::render
	 */
	public function testRender()
	{
		$delimiter	= Message::$delimiter;
		$lineLength	= Message::$lineLength;

		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part	= new Attachment();
		$part->setFile( $file );
		$part->setMimeType( "text/php" );

		$facts	= array(
			'filename="'.basename( $file ).'"',
			'size='.$fileSize,
			'read-date="'.date( "r", $fileATime ).'"',
			'creation-date="'.date( "r", $fileCTime ).'"',
			'modification-date="'.date( "r", $fileMTime ).'"',
		);

		$headers	= array(
			"Content-Disposition: attachment;".$delimiter." ".join( ";".$delimiter." ", $facts ),
			"Content-Type: text/php",
			"Content-Transfer-Encoding: base64",
			"Content-Description: ".basename( $file ),
		);
		$headers	= join( $delimiter, $headers );
		$content	= $this->wrapContent( base64_encode( $content ) );
		$expected	= $headers.$delimiter.$delimiter.$content;
		self::assertEquals( $expected, $part->render() );

		$part->setEncoding( "binary" );
		$part->setMimeType( "application/octet-stream" );

		$headers	= array(
			"Content-Disposition: attachment;".$delimiter." ".join( ";".$delimiter." ", $facts ),
			"Content-Type: application/octet-stream",
			"Content-Transfer-Encoding: binary",
			"Content-Description: ".basename( $file ),
		);
		$headers	= join( $delimiter, $headers );
		$expected	= $headers.$delimiter.$delimiter.$content;
		self::assertEquals( $expected, $part->render() );
	}

	protected function wrapContent( string $content ): string
	{
		return rtrim( chunk_split( $content, $this->lineLength, $this->delimiter ), $this->delimiter );
	}
}
