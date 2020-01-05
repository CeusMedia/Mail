<?php
/**
 *	Inline Image Mail Part.
 *
 *	Copyright (c) 2007-2020 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;

use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Message\Part as MessagePart;
use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

/**
 *	Inline Image Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class InlineImage extends MessagePart
{
	protected $content;
	protected $fileName;
	protected $fileATime		= NULL;
	protected $fileCTime		= NULL;
	protected $fileMTime		= NULL;
	protected $fileSize			= NULL;
	protected $id;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 */
	public function __construct( $id )
	{
		$this->type		= static::TYPE_INLINE_IMAGE;
		$this->setId( $id );
	}

	/**
	 *	Returns set File Name.
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
	 *	@return		integer
	 */
	public function getFileSize()
	{
		return $this->fileSize;
	}

	/**
	 *	Returns latest access time as UNIX timestamp.
	 *	@access		public
	 *	@return		integer
	 */
	public function getFileATime()
	{
		return $this->fileATime;
	}

	/**
	 *	Returns file creation time as UNIX timestamp.
	 *	@access		public
	 *	@return		integer
	 */
	public function getFileCTime()
	{
		return $this->fileCTime;
	}

	/**
	 *	Returns last modification time as UNIX timestamp.
	 *	@access		public
	 *	@return		integer
	 */
	public function getFileMTime()
	{
		return $this->fileMTime;
	}

	/**
	 *	Returns set content ID.
	 *	@access		public
	 *	@return		string
	 */
	public function getId()
	{
		return $this->id;
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
		$headers->setFieldPair( 'Content-Type', $this->mimeType.'; name="'.$this->fileName.'"' );
		$headers->setFieldPair( 'Content-Transfer-Encoding', $this->encoding );
		$headers->setFieldPair( 'Content-ID', '<'.$this->id.'>' );

		$disposition	= array(
			'INLINE',
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
		$headers->setFieldPair( 'Content-Disposition', join( '; ', $disposition ) );

		$content	= static::encodeContent( $this->content, $this->encoding );
		$delim		= Message::$delimiter;
		return $headers->toString().$delim.$delim.$content;
	}

	/**
	 *	Sets inline image by existing file.
	 *	Will gather file size, file dates (access, creation, modification).
	 *	@access		public
	 *	@param		string			$filePath		Path to image file
	 *	@param		string|NULL		$mimeType		Optional: MIME type of file (will be detected if not given)
	 *	@param		string|NULL		$encoding		Optional: Encoding of file
	 *	@param		string|NULL		$fileName		Optional: Name of file in part
	 *	@return		object		  	Self instance for chaining
	 *	@throws		\InvalidArgumentException	if file is not existing
	 *	@todo  		scan file for malware
	 */
	public function setFile( string $filePath, string $mimeType = NULL, string $encoding = NULL, string $fileName = NULL ): self
 	{
		$file	= new \FS_File( $filePath );
 		if( !$file->exists() )
 			throw new \InvalidArgumentException( 'Inline file "'.$filePath.'" is not existing' );
 		$this->content	= $file->getContent();
		$this->setFileName( $fileName ? $fileName : basename( $filePath ) );
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
	 *	@param		string   	$fileName		File name
	 *	@return		self	  	Self instance for chaining
	 */
	public function setFileName( $fileName ): self
	{
		$this->fileName		= basename( $fileName );
		return $this;
	}

	/**
	 *	Sets file size in bytes.
	 *	@access		public
	 *	@param		integer  	$fileSize		File size
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
	 *	@param		integer   	$timestamp		Timestamp of latest access
	 *	@return		self  		Self instance for chaining
	 */
	public function setFileATime( $timestamp ): self
	{
		$this->fileATime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets creation time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer   	$timestamp		Timestamp of creation
	 *	@return		self  		Self instance for chaining
	 */
	public function setFileCTime( $timestamp ): self
	{
		$this->fileCTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets modification time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer  	$timestamp		Timestamp of last modification
	 *	@return		self  		Self instance for chaining
	 */
	public function setFileMTime( $timestamp ): self
	{
		$this->fileMTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets content ID of image to be used in HTML part.
	 *	@access		public
	 *	@param		string   	$id				Content ID of image to be used in HTML part
	 *	@return		self	  	Self instance for chaining
	 */
	public function setId( $id ): self
	{
		$this->id	= $id;
		return $this;
	}
}
