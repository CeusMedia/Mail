<?php
/**
 *	Inline Image Mail Part.
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
 *	Inline Image Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 */
class InlineImage extends \CeusMedia\Mail\Part{

	protected $content;
	protected $fileName;
	protected $id;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@return		void
	 */
	public function __construct( $id, $fileName, $mimeType = NULL, $encoding = NULL ){
		$this->setFormat( 'fixed' );
		$this->setEncoding( 'base64' );
//		$this->setMimeType( 'application/octet-stream' );
		$this->setFile( $id, $fileName, $mimeType, $encoding );
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
	 *	Returns set content ID.
	 *	@access		public
	 *	@return		string
	 */
	public function getId(){
		return $this->id;
	}

	protected function getMimeTypeFromFile( $fileName ){
		if( !file_exists( $fileName ) )
			throw new \InvalidArgumentException( 'File "'.$fileName.'" is not existing' );
		$finfo	= finfo_open( FILEINFO_MIME_TYPE );
		$type	= finfo_file( $finfo, $fileName );
		finfo_close( $finfo );
		return $type;
	}

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
		$headers['Content-Disposition']	= join( ";\r\n ", $headers['Content-Disposition'] );
		$headers['Content-Type']		= join( ";\r\n ", $headers['Content-Type'] );

		$section	= new \CeusMedia\Mail\Header\Section();
		foreach( $headers as $key => $value )
			$section->setFieldPair( $key, $value );

		$content	= $this->encode( $this->content, $this->encoding );
		return $section->toString()."\r\n\r\n".$content;
	}

	public function setFile( $id, $fileName, $mimeType = NULL, $encoding = NULL ){
		if( !file_exists( $fileName ) )
			throw new \InvalidArgumentException( 'Attachment file "'.$fileName.'" is not existing' );
		$this->content	= file_get_contents( $fileName );
		$this->fileName	= $fileName;
		$this->id		= $id;
		$mimeType		= $mimeType ? $mimeType : $this->getMimeTypeFromFile( $fileName );
		$this->setMimeType( $mimeType );
		if( $encoding )
			$this->setEncoding( $encoding );
	}
}
?>
