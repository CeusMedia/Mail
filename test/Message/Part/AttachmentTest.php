<?php
/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */
//require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part;
use CeusMedia\Mail\Message\Part\Attachment;

/**
 *	Unit test for mail message part.
 *	@category			Test
 *	@package			CeusMedia_Mail_Message_Part
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Part\Attachment
 */
class Message_Part_AttachmentTest extends TestCase
{
	protected $delimiter;
	protected $lineLength;

	public function __construct(){
		parent::__construct();
		$this->delimiter	= Message::$delimiter;
		$this->lineLength	= Message::$lineLength;
	}

	/**
	 *	@covers		::__construct
	 */
	public function testConstruct(){
		$part	= new Attachment();
		$this->assertEquals( 'fixed', $part->getFormat() );
		$this->assertEquals( 'base64', $part->getEncoding() );
		$this->assertEquals( 'application/octet-stream', $part->getMimeType() );
	}

	/**
	 *	@covers		::getFileATime
	 *	@covers		::setFileATime
	 */
	public function testFileATime(){
		$time	= time() - 30;
		$part	= new Attachment();
		$part->setFileATime( $time );
		$this->assertEquals( $time, $part->getFileATime() );
	}

	/**
	 *	@covers		::getFileCTime
	 *	@covers		::setFileCTime
	 */
	public function testFileCTime(){
		$time	= time() - 30;
		$part	= new Attachment();
		$part->setFileCTime( $time );
		$this->assertEquals( $time, $part->getFileCTime() );
	}

	/**
	 *	@covers		::getFileMTime
	 *	@covers		::setFileMTime
	 */
	public function testFileMTime(){
		$time	= time() - 30;
		$part	= new Attachment();
		$part->setFileMTime( $time );
		$this->assertEquals( $time, $part->getFileMTime() );
	}

	/**
	 *	@covers		::getFileSize
	 *	@covers		::setFileSize
	 */
	public function testFileSize(){
		$size	= 1234;
		$part	= new Attachment();
		$part->setFileSize( $size );
		$this->assertEquals( $size, $part->getFileSize() );
	}

	/**
	 *	@covers		::getContent
	 *	@covers		::setContent
	 */
	public function testContent(){
		$content	= "This is the content. It contains umlauts: äöü.";
		$part		= new Attachment();

		$part->setContent( $content );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( 'application/octet-stream', $part->getMimeType() );
		$this->assertEquals( 'base64', $part->getEncoding() );

		$part->setContent( $content );
		$part->setMimeType( 'x-zip' );
		$part->setEncoding( 'binary' );
		$this->assertEquals( $content, $part->getContent() );
		$this->assertEquals( 'x-zip', $part->getMimeType() );
		$this->assertEquals( 'binary', $part->getEncoding() );
	}

	/**
	 *	@covers		::setFile
	 */
	public function testFile(){
		$file		= __FILE__;
		$content	= file_get_contents( $file );
		$fileATime	= fileatime( $file );
		$fileCTime	= filectime( $file );
		$fileMTime	= filemtime( $file );
		$fileSize	= filesize( $file );

		$part		= new Attachment();
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
	 *	@covers		::setFile
	 */
	public function testSetFileException(){
		$this->expectException( 'InvalidArgumentException' );
		$part	= new Attachment();
		$part->setFile( 'invalid' );
	}

	/**
	*	@covers		::getFileName
	 *	@covers		::setFileName
	 */
	public function testFileName(){
		$part	= new Attachment();
		$this->assertEquals( NULL, $part->getFileName() );
		$part->setFileName( "test.file" );
		$this->assertEquals( "test.file", $part->getFileName() );
		$part->setFileName( "path/test.file" );
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
		$this->assertEquals( $expected, $part->render() );

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
		$this->assertEquals( $expected, $part->render() );
	}

	protected function wrapContent( $content ){
		return rtrim( chunk_split( $content, $this->lineLength, $this->delimiter ), $this->delimiter );
	}
}
