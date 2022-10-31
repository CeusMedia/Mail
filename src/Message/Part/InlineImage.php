<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	Inline Image Mail Part.
 *
 *	Copyright (c) 2007-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;

use CeusMedia\Common\Exception\IO as IoException;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part as MessagePart;
use CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;

use CeusMedia\Common\FS\File;
use InvalidArgumentException;

use function array_reverse;
use function basename;
use function date;
use function fileatime;
use function filectime;
use function filemtime;
use function filesize;
use function join;
use function strlen;
use function trim;

/**
 *	Inline Image Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class InlineImage extends MessagePart
{
	/**	@var	string|NULL			$fileName */
	protected ?string $fileName		= NULL;

	/**	@var	int|NULL			$fileATime */
	protected ?int $fileATime		= NULL;

	/**	@var	int|NULL			$fileCTime */
	protected ?int $fileCTime		= NULL;

	/**	@var	int|NULL			$fileMTime */
	protected ?int $fileMTime		= NULL;

	/**	@var	int|NULL			$fileSize */
	protected ?int $fileSize		= NULL;

	/**	@var	string				$id */
	protected string $id;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 */
	public function __construct( string $id )
	{
		$this->type		= static::TYPE_INLINE_IMAGE;
		$this->setEncoding( 'base64' );
		$this->setId( $id );
	}

	/**
	 *	Returns latest access time as UNIX timestamp.
	 *	@access		public
	 *	@return		integer|NULL
	 */
	public function getFileATime(): ?int
	{
		return $this->fileATime;
	}

	/**
	 *	Returns file creation time as UNIX timestamp.
	 *	@access		public
	 *	@return		integer|NULL
	 */
	public function getFileCTime(): ?int
	{
		return $this->fileCTime;
	}

	/**
	 *	Returns last modification time as UNIX timestamp.
	 *	@access		public
	 *	@return		integer|NULL
	 */
	public function getFileMTime(): ?int
	{
		return $this->fileMTime;
	}

	/**
	 *	Returns set File Name.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getFileName(): ?string
	{
		return $this->fileName;
	}

	/**
	 *	Returns file size in bytes.
	 *	@access		public
	 *	@return		integer|NULL
	 */
	public function getFileSize(): ?int
	{
		return $this->fileSize;
	}

	/**
	 *	Returns set content ID.
	 *	@access		public
	 *	@return		string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 *	Returns string representation of mail part for rendering whole mail.
	 *	@access		public
	 *	@param		integer						$sections				Section(s) to render, default: all
	 *	@param		MessageHeaderSection|NULL	$additionalHeaders		Section with header fields to render aswell
	 *	@return		string
	 */
	public function render( int $sections = self::SECTION_ALL, ?MessageHeaderSection $additionalHeaders = NULL ): string
	{
		$doAll		= self::SECTION_ALL === ( $sections & self::SECTION_ALL );
		$doHeader	= self::SECTION_HEADER === ( $sections & self::SECTION_HEADER );
		$doContent	= self::SECTION_CONTENT === ( $sections & self::SECTION_CONTENT );
		$delim		= Message::$delimiter;
		$list		= [];

		$section	= $additionalHeaders ?? new MessageHeaderSection();

		if( $doContent || $doAll ){
			$content	= static::encodeContent( $this->content, $this->encoding );
			$list[]		= $content;
//			$section->setFieldPair( 'Content-Length', (string) $content );
		}

		if( $doHeader || $doAll ){
			$section->setFieldPair( 'Content-Type', $this->mimeType.'; name="'.$this->fileName.'"' );
			$section->setFieldPair( 'Content-Transfer-Encoding', $this->encoding );
			$section->setFieldPair( 'Content-ID', '<'.$this->id.'>' );
			$disposition	= [
				'INLINE',
				'filename="'.$this->fileName.'"'
			];
			if( NULL !== $this->fileSize )
				$disposition[]	= 'size="'.$this->fileSize.'"';
			if( NULL !== $this->fileATime )
				$disposition[]	= 'read-date="'.date( 'r', $this->fileATime ).'"';
			if( NULL !== $this->fileCTime )
				$disposition[]	= 'creation-date="'.date( 'r', $this->fileCTime ).'"';
			if( NULL !== $this->fileMTime )
				$disposition[]	= 'modification-date="'.date( 'r', $this->fileMTime ).'"';
			$section->setFieldPair( 'Content-Disposition', join( '; ', $disposition ) );
			$list[]		= $section->toString( TRUE );
		}

		return join( $delim.$delim, array_reverse( $list ) );
	}

	/**
	 *	Sets inline image by existing file.
	 *	Will gather file size, file dates (access, creation, modification).
	 *	@access		public
	 *	@param		string			$filePath		Path to image file
	 *	@param		string|NULL		$mimeType		Optional: MIME type of file (will be detected if not given)
	 *	@param		string|NULL		$encoding		Optional: Encoding of file
	 *	@param		string|NULL		$fileName		Optional: Name of file in part
	 *	@return		self			Self instance for chaining
	 *	@throws		InvalidArgumentException	if file is not existing
	 *	@throws		IoException					if reading from file failed
	 *	@todo		scan file for malware
	 */
	public function setFile( string $filePath, string $mimeType = NULL, string $encoding = NULL, string $fileName = NULL ): self
	{
		$file	= new File( $filePath );
		/** @noinspection PhpUnhandledExceptionInspection */
		if( !$file->exists() )
			throw new InvalidArgumentException( 'Inline file "'.$filePath.'" is not existing' );

		if( NULL === $mimeType || 0 === strlen( trim( $mimeType ) ) )
			$mimeType	= $this->getMimeTypeFromFile( $filePath );
		if( NULL === $fileName || 0 === strlen( trim( $fileName ) ) )
			$fileName	= basename( $filePath );
 		$this->content	= (string) $file->getContent( TRUE );

		$this->setFileName( $fileName );
		if( FALSE !== ( $fileSize = filesize( $filePath ) ) )
			$this->setFileSize( $fileSize );
		if( FALSE !== ( $fileATime = fileatime( $filePath ) ) )
			$this->setFileATime( $fileATime );
		if( FALSE !== ( $fileCTime = filectime( $filePath ) ) )
			$this->setFileCTime( $fileCTime );
		if( FALSE !== ( $fileMTime = filemtime( $filePath ) ) )
			$this->setFileMTime ($fileMTime );
		if( NULL !== $mimeType )
			$this->setMimeType( $mimeType );
		if( NULL !== $encoding && 0 !== strlen( trim( $encoding ) ) )
			$this->setEncoding( $encoding );
		return $this;
	}

	/**
	 *	Sets access time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer		$timestamp		Timestamp of latest access
	 *	@return		self		Self instance for chaining
	 */
	public function setFileATime( int $timestamp ): self
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
	public function setFileCTime( int $timestamp ): self
	{
		$this->fileCTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets modification time by UNIX timestamp.
	 *	@access		public
	 *	@param		integer		$timestamp		Timestamp of last modification
	 *	@return		self		Self instance for chaining
	 */
	public function setFileMTime( int $timestamp ): self
	{
		$this->fileMTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets file name.
	 *	@access		public
	 *	@param		string		$fileName		File name
	 *	@return		self		Self instance for chaining
	 */
	public function setFileName( string $fileName ): self
	{
		$this->fileName		= basename( $fileName );
		return $this;
	}

	/**
	 *	Sets file size in bytes.
	 *	@access		public
	 *	@param		integer|NULL	$fileSize		File size
	 *	@return		self			Self instance for chaining
	 */
	public function setFileSize( ?int $fileSize = NULL ): self
	{
		$this->fileSize		= $fileSize;
		return $this;
	}

	/**
	 *	Sets content ID of image to be used in HTML part.
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@return		self		Self instance for chaining
	 */
	public function setId( string $id ): self
	{
		$this->id	= $id;
		return $this;
	}
}
