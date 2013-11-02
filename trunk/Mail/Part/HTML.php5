<?php
class CMM_Mail_Part_HTML extends CMM_Mail_Part_Text{

	public function __construct( $content ){
		parent::__construct( $content );
		$this->setMimeType( 'text/html' );
	}
}
