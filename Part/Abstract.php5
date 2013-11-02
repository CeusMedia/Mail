<?php
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

	abstract public function render();
}
?>