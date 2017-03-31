<?php
/**
 *	Inline Image Mail Part.
 *
 *	Copyright (c) 2007-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;
/**
 *	Inline Image Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class InlineImage extends \CeusMedia\Mail\Message\Part{

	protected $content;
	protected $fileName;
	protected $fileATime		= NULL;
	protected $fileCTime		= NULL;
	protected $fileMTime		= NULL;
	protected $fileSize			= NULL;
	protected $id;

	/**
	 *	Constructor.
	 *	Sets image by existing file.
	 *	Will gather file size, file dates (access, creation, modification).
	 *	@access		public
	 *	@param		string		$id				Content ID of image to be used in HTML part
	 *	@param		string		$fileName		Name of file to attach
	 *	@param		string		$mimeType		MIME type of file (will be detected if not given)
	 *	@param		string		$encoding		Encoding of file
	 *	@return		object  	Instance
	 *	@throws		\InvalidArgumentException	if file is not existing
	 *	@todo  		scan file for malware
	 */
	public function __construct( $id, $fileName, $mimeType = NULL, $encoding = NULL ){
		if( !file_exists( $fileName ) )
			throw new \InvalidArgumentException( 'Attachment file "'.$fileName.'" is not existing' );
		$this->setFormat( 'fixed' );
		$this->setEncoding( 'base64' );
//		$this->setMimeType( 'application/octet-stream' );
		$this->content	= file_get_contents( $fileName );
		$this->setId( $id );
		$this->setFileName( $fileName );
		$this->setFileSize( filesize( $fileName ) );
		$this->setFileATime( fileatime( $fileName ) );
		$this->setFileCTime( filectime( $fileName ) );
		$this->setFileMTime( filemtime( $fileName ) );
		$this->setMimeType( $mimeType ? $mimeType : $this->getMimeTypeFromFile( $fileName ) );
		if( $encoding )
			$this->setEncoding( $encoding );
	}

	/**
	 *	Returns set File Name.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileName(){
		return $this->fileName;
	}

	/**
	 *	Returns file size in bytes.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileSize(){
		return $this->fileSize;
	}

	/**
	 *	Returns latest access time as UNIX timestamp.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileATime(){
		return $this->fileATime;
	}

	/**
	 *	Returns file creation time as UNIX timestamp.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileCTime(){
		return $this->fileCTime;
	}

	/**
	 *	Returns last modification time as UNIX timestamp.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileMTime(){
		return $this->fileMTime;
	}

	/**
	 *	Returns set content ID.
	 *	@access		public
	 *	@return		string
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 *	Returns string representation of mail part for rendering whole mail.
	 *	@access		public
	 *	@return		string
	 */
	public function render(){
		$headers		= array(
			'Content-Disposition'		=> array(
				"INLINE"
			),
			'Content-Type'				=> array(
				$this->mimeType,
				'name="'.$this->fileName.'"',
			),
			'Content-Transfer-Encoding'	=> $this->encoding,
			'Content-ID'				=> '<'.$this->id.'>',
		);
		if( $this->fileSize !== NULL )
			$headers['Content-Disposition'][]	= 'size='.$this->fileSize;
		if( $this->fileATime !== NULL )
			$headers['Content-Disposition'][]	= 'read-date="'.date( 'r', $this->fileATime ).'"';
		if( $this->fileCTime !== NULL )
			$headers['Content-Disposition'][]	= 'creation-date="'.date( 'r', $this->fileCTime ).'"';
		if( $this->fileMTime !== NULL )
			$headers['Content-Disposition'][]	= 'modification-date="'.date( 'r', $this->fileMTime ).'"';
		$headers['Content-Disposition']	= join( ";\r\n ", $headers['Content-Disposition'] );
		$headers['Content-Type']		= join( ";\r\n ", $headers['Content-Type'] );

		$section	= new \CeusMedia\Mail\Message\Header\Section();
		foreach( $headers as $key => $value )
			$section->setFieldPair( $key, $value );

		$content	= $this->encode( $this->content, $this->encoding );
		return $section->toString()."\r\n\r\n".$content;
	}

	/**
	 *	Sets inline image by existing file.
	 *	@access		public
	 *	@param		string		$fileName		Name of file to attach
	 *	@param		string		$mimeType		MIME type of file (will be detected if not given)
	 *	@param		string		$encoding		Encoding of file
	 *	@return		object  	Self instance for chaining
	 *	@throws		\InvalidArgumentException	if file is not existing
	 *	@todo   	use mime magic to detect MIME type
	 *	@todo  		scan file for malware
	 *	@deprecated	use constructor instead
	 *	@todo    	to be removed in 1.2
	 */
	public function setFile( $id, $fileName, $mimeType = NULL, $encoding = NULL ){
		trigger_error( 'Use constructor instead', E_USER_DEPRECATED );
		return new self( $id, $fileName, $mimeType, $encoding );
	}

	/**
	 *	Sets file name.
	 *	@access		public
	 *	@param		string   	File name
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileName( $fileName ){
		$this->fileName		= basename( $fileName );
		return $this;
	}

	/**
	 *	Sets file size in bytes.
	 *	@access		public
	 *	@param		string   	File name.
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileSize( $size ){
		$this->fileSize		= $size;
		return $this;
	}

	/**
	 *	Sets access time by UNIX timestamp.
	 *	@access		public
	 *	@param		string   	$timestamp		Timestamp of latest access
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileATime( $timestamp ){
		$this->fileATime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets creation time by UNIX timestamp.
	 *	@access		public
	 *	@param		string   	$timestamp		Timestamp of creation
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileCTime( $timestamp ){
		$this->fileCTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets modification time by UNIX timestamp.
	 *	@access		public
	 *	@param		string   	$timestamp		Timestamp of last modification
	 *	@return		object  	Self instance for chaining
	 */
	public function setFileMTime( $timestamp ){
		$this->fileMTime	= $timestamp;
		return $this;
	}

	/**
	 *	Sets content ID of image to be used in HTML part.
	 *	@access		public
	 *	@param		string   	$id				Content ID of image to be used in HTML part
	 *	@return		object  	Self instance for chaining
	 */
	public function setId( $id ){
		$this->id	= $id;
		return $this;
	}
}
?>
