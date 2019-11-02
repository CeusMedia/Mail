<?php
/**
 *	Attachment Mail Part.
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Part as MessagePart;
use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

/**
 *	Attachment Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Attachment extends MessagePart
{
	protected $content;
	protected $fileName;
	protected $fileATime		= NULL;
	protected $fileCTime		= NULL;
	protected $fileMTime		= NULL;
	protected $fileSize			= NULL;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct()
	{
		$this->setFormat( 'fixed' );
		$this->setEncoding( 'base64' );
		$this->setMimeType( 'application/octet-stream' );
	}

	/**
	 *	Returns set file name.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 *	Returns file size in bytes.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileSize()
	{
		return $this->fileSize;
	}

	/**
	 *	Returns latest access time as UNIX timestamp.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileATime()
	{
		return $this->fileATime;
	}

	/**
	 *	Returns file creation time as UNIX timestamp.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileCTime()
	{
		return $this->fileCTime;
	}

	/**
	 *	Returns last modification time as UNIX timestamp.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getFileMTime()
	{
		return $this->fileMTime;
	}

	/**
	 *	Returns string representation of mail part for rendering whole mail.
	 *	@access		public
	 *	@param		boolean		$headers		Flag: render part with headers
	 *	@return		string
	 */
	public function render( $headers = NULL ): string
	{
		if( !$headers )
			$headers	= new MessageHeaderSection();


		$disposition	= array(
			'attachment',
			'filename="'.$this->fileName.'"'
		);
		if( $this->fileSize !== NULL )
			$disposition[]	= 'size='.$this->fileSize;
		if( $this->fileATime !== NULL )
			$disposition[]	= 'read-date="'.date( 'r', $this->fileATime ).'"';
		if( $this->fileCTime !== NULL )
			$disposition[]	= 'creation-date="'.date( 'r', $this->fileCTime ).'"';
		if( $this->fileMTime !== NULL )
			$disposition[]	= 'modification-date="'.date( 'r', $this->fileMTime ).'"';

		$delim		= Message::$delimiter;
		$headers->setFieldPair( 'Content-Disposition', join( ';'.$delim.' ', $disposition ) );
		$headers->setFieldPair( 'Content-Type', $this->mimeType );
		$headers->setFieldPair( 'Content-Transfer-Encoding', $this->encoding );
		$headers->setFieldPair( 'Content-Description', $this->fileName );

		$content	= static::encodeContent( $this->content, $this->encoding );
		return $headers->toString( TRUE ).$delim.$delim.$content;
	}

	/**
	 *	Sets attachment by content string.
	 *	@access		public
	 *	@param		string		$content		String containing file content
	 *	@param		string		$mimeType		Optional: MIME type of file (will NOT be detected if not given)
	 *	@param		string		$encoding		Optional: Encoding of file
	 *	@return		object  	Self instance for chaining
	 *	@todo   	use mime magic to detect MIME type
	 *	@todo  		scan file for malware
	 */
	public function setContent( $content, $mimeType = NULL, $encoding = NULL ){
		$this->content	= $content;
		if( $mimeType )
			$this->setMimeType( $mimeType );
		if( $encoding )
			$this->setEncoding( $encoding );
		return $this;
	}

	/**
	 *	Sets attachment by existing file.
	 *	Will gather file size, file dates (access, creation, modification).
	 *	@access		public
	 *	@param		string		$filePath		Path of file to attach
	 *	@param		string		$mimeType		Optional: MIME type of file (will be detected if not given)
	 *	@param		string		$encoding		Optional: Encoding of file
	 *	@param		string		$fileName		Optional: Name of file
	 *	@return		object  	Self instance for chaining
	 *	@throws		\InvalidArgumentException	if file is not existing
	 *	@todo  		scan file for malware
	 */
	public function setFile( $filePath, $mimeType = NULL, $encoding = NULL, $fileName = NULL ): self
	{
		if( !file_exists( $filePath ) )
			throw new \InvalidArgumentException( 'Attachment file "'.$filePath.'" is not existing' );
		$this->content	= file_get_contents( $filePath );
		$this->setFileName( $fileName ? $fileName : $filePath );
		$this->setFileSize( filesize( $filePath ) );
		$this->setFileATime( fileatime( $filePath ) );
		$this->setFileCTime( filectime( $filePath ) );
		$this->setFileMTime( filemtime( $filePath ) );
		$this->setMimeType( $mimeType ? $mimeType : $this->getMimeTypeFromFile( $filePath ) );
		if( $encoding )
			$this->setEncoding( $encoding );
		return $this;
	}

	/**
	 *	Sets file name.
	 *	@access		public
	 *	@param		string   	$fileName	File name.
	 *	@return		self  		Self instance for chaining
	 */
	public function setFileName( $fileName ): self
	{
		$this->fileName		= basename( $fileName );
		return $this;
	}

	/**
	 *	Sets file size in bytes.
	 *	@access		public
	 *	@param		integer   	$fileSize		File size in bytes.
	 *	@return		self  		Self instance for chaining
	 */
	public function setFileSize( $fileSize ): self
	{
		$this->fileSize		= $fileSize;
		return $this;
	}

	/**
	 *	Sets access time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer   	$timestamp		Timestamp of latest access.
	 *	@return		self	  	Self instance for chaining
	 */
	public function setFileATime( $timestamp ): self
	{
		$this->fileATime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets creation time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer   	$timestamp		Timestamp of creation.
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileCTime( $timestamp ): self
	{
		$this->fileCTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets modification time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer   	$timestamp		Timestamp of last modification.
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileMTime( $timestamp ): self
	{
		$this->fileMTime	= $timestamp;
		return $this;
	}
}
