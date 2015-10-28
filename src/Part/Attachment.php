<?php
/**
 *	Attachment Mail Part.
 *
 *	Copyright (c) 2007-2015 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Part;
/**
 *	Attachment Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class Attachment extends \CeusMedia\Mail\Part{

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
	public function __construct(){
		$this->setFormat( 'fixed' );
		$this->setEncoding( 'base64' );
		$this->setMimeType( 'application/octet-stream' );
	}

	/**
	 *	Returns set File Name.
	 *	@access		public
	 *	@return		string
	 */
	public function getFileName(){
		return $this->fileName;
	}

	public function getFileSize(){
		return $this->fileSize;
	}

	public function getFileATime(){
		return $this->fileATime;
	}

	public function getFileCTime(){
		return $this->fileCTime;
	}

	public function getFileMTime(){
		return $this->fileMTime;
	}

	public function render(){
		$headers		= array(
			'Content-Disposition'		=> array(
				"attachment",
				'filename="'.$this->fileName.'"',
			),
			'Content-Type'				=> array(
				$this->mimeType,
			),
			'Content-Transfer-Encoding'	=> $this->encoding,
			'Content-Description'		=> $this->fileName,
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

		$section	= new \CeusMedia\Mail\Header\Section();
		foreach( $headers as $key => $value )
			$section->setFieldPair( $key, $value );

		$content	= $this->encode( $this->content, $this->encoding );
		return $section->toString()."\r\n\r\n".$content;
	}

	/**
	 *	Sets attachment by content string.
	 *	@access		public
	 *	@param		string		$content		String containing file content
	 *	@param		string		$fileName		Name of File to attach
	 *	@param		string		$mimeType		MIME Type of File
	 *	@return		void
	 */
	public function setContent( $content, $mimeType = NULL, $encoding = NULL ){
		$this->content	= $content;
		if( $mimeType )
			$this->setMimeType( $mimeType );
		if( $encoding )
			$this->setEncoding( $encoding );
	}

	public function setFile( $fileName, $mimeType = NULL, $encoding = NULL ){
		if( !file_exists( $fileName ) )
			throw new \InvalidArgumentException( 'Attachment file "'.$fileName.'" is not existing' );
		$this->setFileName( $fileName );
		$this->content	= file_get_contents( $fileName );
		$this->setFileSize( filesize( $fileName ) );
		$this->setFileATime( fileatime( $fileName ) );
		$this->setFileCTime( filectime( $fileName ) );
		$this->setFileMTime( filemtime( $fileName ) );
		if( $mimeType )
			$this->setMimeType( $mimeType );
		if( $encoding )
			$this->setEncoding( $encoding );
	}

	public function setFileName( $fileName ){
		$this->fileName		= basename( $fileName );
	}

	public function setFileSize( $size ){
		$this->fileSize		= $size;
	}

	public function setFileATime( $timestamp ){
		$this->fileATime	= $timestamp;
	}

	public function setFileCTime( $timestamp ){
		$this->fileCTime	= $timestamp;
	}

	public function setFileMTime( $timestamp ){
		$this->fileMTime	= $timestamp;
	}
}
?>
