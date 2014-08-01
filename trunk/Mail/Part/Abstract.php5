<?php
/**
 *	Abstract Mail Part.
 *
 *	Copyright (c) 2010-2014 Christian Würker (ceusmedia.de)
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
 *	@category		cmModules
 *	@package		Mail.Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@version		$Id: Attachment.php5 1080 2013-07-23 01:56:47Z christian.wuerker $
 */
/**
 *	Abstract Mail Part.
 *
 *	@category		cmModules
 *	@package		Mail.Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2014 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmmodules/
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@version		$Id: Attachment.php5 1080 2013-07-23 01:56:47Z christian.wuerker $
 */
abstract class CMM_Mail_Part_Abstract{

	protected $charset;
	protected $content;
	protected $encoding;
	protected $format;
	protected $mimeType;

	public function getCharset(){
		return $this->charset;
	}
	
	public function getContent(){
		return $this->content;
	}

	public function getEncoding(){
		return $this->encoding;
	}

	public function getFormat(){
		return $this->format;
	}
	
	public function getMimeType(){
		return $this->mimeType;
	}

	abstract public function render();

	public function setCharset( $charset ){
		$this->charset	= $charset;
	}

	public function setContent( $content ){
		$this->content	= $content;
	}

	public function setEncoding( $encoding ){
		$encodings	= array( "7bit", "8bit", "base64", "quoted-printable", "binary" );
		if( !in_array( $encoding, $encodings ) )
			throw new InvalidArgumentException( 'Invalid encoding' );
		$this->encoding	= $encoding;
	}

	public function setFormat( $format ){
		$formats	= array( "fixed", "flowed" );
		if( !in_array( $format, $formats ) )
			throw new InvalidArgumentException( 'Invalid format' );
		$this->format	= $format;
	}

	public function setMimeType( $mimeType ){
		$this->mimeType	= $mimeType;
	}
}
?>
