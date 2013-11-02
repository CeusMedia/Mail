<?php
class CMM_Mail_Part_Text extends CMM_Mail_Part_Abstract{

	public function __construct( $content, $charset = 'UTF-8', $encoding = 'quoted-printable' ){
		$this->setContent( $content );
		$this->setMimeType( 'text/plain' );
		$this->setCharset( $charset );
		$this->setFormat( 'fixed' );
		$this->setEncoding( $encoding );
	}

	public function render(){
		switch( strtolower( $this->encoding ) ){
			case 'base64':
				$content	= base64_encode( $this->content );
				break;
			case 'quoted-printable':
				$content	= quoted_printable_encode( $this->content );
				break;
			default:
				$content	= $this->content;
		}
		$content		= chunk_split( $content, 76 );
		$headers		= new CMM_Mail_Header_Section();
		$contentType	= array(
			$this->mimeType,
			'charset="'.trim( $this->charset ).'"',
//			'format="'.$this->format.'"'
		);
		$headers->addFieldPair( 'Content-Type', join( ";\r\n ", $contentType ) );
		$headers->addFieldPair( 'Content-Transfer-Encoding', $this->encoding );
		return $headers->toString()."\r\n"."\r\n".$content;
	}
}
