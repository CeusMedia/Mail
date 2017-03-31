<?php
/**
 *	Unit test for mail message part.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';
/**
 *	Unit test for mail message part.
 *	@category		Test
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Message_Part_AttachmentTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct(){
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$this->assertEquals( 'fixed', $part->getFormat() );
		$this->assertEquals( 'base64', $part->getEncoding() );
		$this->assertEquals( 'application/octet-stream', $part->getMimeType() );
	}

	public function testFileATime(){
		$time	= time() - 30;
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFileATime( $time );
		$this->assertEquals( $time, $part->getFileATime() );
	}

	public function testFileCTime(){
		$time	= time() - 30;
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFileCTime( $time );
		$this->assertEquals( $time, $part->getFileCTime() );
	}

	public function testFileMTime(){
		$time	= time() - 30;
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFileMTime( $time );
		$this->assertEquals( $time, $part->getFileMTime() );
	}

	public function testFileSize(){
		$size	= 1234;
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFileSize( $size );
		$this->assertEquals( $size, $part->getFileSize() );
	}

	public function testContent(){
		$content	= "This is the content. It contains umlauts: äöü.";
		$part		= new \CeusMedia\Mail\Message\Part\Attachment();

		$part->setContent( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( 'application/octet-stream', $part->getMimeType() );
		$this->assertEquals( 'base64', $part->getEncoding() );

		$part->setContent( $content, 'x-zip', 'binary' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( 'x-zip', $part->getMimeType() );
		$this->assertEquals( 'binary', $part->getEncoding() );
	}

	public function testFile(){
		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part		= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFile( $file );
		$this->assertEquals( basename( __FILE__ ), $part->getFileName() );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( $fileATime, $part->getFileATime() );
		$this->assertEquals( $fileCTime, $part->getFileCTime() );
		$this->assertEquals( $fileMTime, $part->getFileMTime() );
		$this->assertEquals( $fileSize, $part->getFileSize() );

		$part->setFile( $file, 'x-zip', 'binary' );
		$this->assertEquals( basename( __FILE__ ), $part->getFileName() );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( 'x-zip', $part->getMimeType() );
		$this->assertEquals( 'binary', $part->getEncoding() );
	}

	/**
	 *	@expectedException	\InvalidArgumentException
	 */
	public function testSetFileException(){
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$part->setFile( 'invalid' );
	}

	public function testFileName(){
		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
		$this->assertEquals( NULL, $part->getFileName() );
		$part->setFileName( "test.file" );
		$this->assertEquals( "test.file", $part->getFileName() );

		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part->setFile( $file );
		$this->assertEquals( basename( __FILE__ ), $part->getFileName() );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( $fileATime, $part->getFileATime() );
		$this->assertEquals( $fileCTime, $part->getFileCTime() );
		$this->assertEquals( $fileMTime, $part->getFileMTime() );
		$this->assertEquals( $fileSize, $part->getFileSize() );

		$part->setFileName( "test.file" );
		$this->assertEquals( "test.file", $part->getFileName() );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( $fileATime, $part->getFileATime() );
		$this->assertEquals( $fileCTime, $part->getFileCTime() );
		$this->assertEquals( $fileMTime, $part->getFileMTime() );
		$this->assertEquals( $fileSize, $part->getFileSize() );
	}

	public function testRender()
	{
		$delimiter	= \CeusMedia\Mail\Message::$delimiter;
		$lineLength	= \CeusMedia\Mail\Message::$lineLength;

		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part	= new \CeusMedia\Mail\Message\Part\Attachment();
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
		$content	= chunk_split( base64_encode( $content ), $lineLength, $delimiter );
		$assertion	= $headers.$delimiter.$delimiter.$content;
		$this->assertEquals( $assertion, $part->render() );

		$part->setEncoding( "binary" );
		$part->setMimeType( "application/octet-stream" );

		$headers	= array(
			"Content-Disposition: attachment;".$delimiter." ".join( ";".$delimiter." ", $facts ),
			"Content-Type: application/octet-stream",
			"Content-Transfer-Encoding: binary",
			"Content-Description: ".basename( $file ),
		);
		$headers	= join( $delimiter, $headers );
		$assertion	= $headers.$delimiter.$delimiter.$content;
		$this->assertEquals( $assertion, $part->render() );

	}
}
