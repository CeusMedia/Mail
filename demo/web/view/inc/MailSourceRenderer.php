<?php

namespace CeusMedia\MailDemo\Web\View;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Renderer;

class MailSourceRenderer
{
	protected $message;

	public function __construct( Message $message )
	{
		$this->message	= $message;
	}

	public function render()
	{
		$code	= Renderer::render( $this->message );
		return $this->shortenMailCode( $code );
	}

	protected function shortenMailCode( $code )
	{
		$status	= 0;
		$list	= [];
		foreach( explode( PHP_EOL, $code ) as $nr => $line ){
			$isEmpty	= !strlen( trim( $line ) );
			$isBased	= preg_match( '/^[\S]{74,80}$/', trim( $line ) );
			if( !$isEmpty && !$isBased ){
				if( $status === 3 ){
					$status	= 0;
					continue;
				}
				$status	= 0;
			}
			else if( $isEmpty )
				$status	= 1;
			else if( $status === 1 && $isBased )
				$status++;
			else if( $status === 2 && $isBased ){
				$list[count( $list ) - 1]	= '[data encoded with base64]';
				$status++;
			}
			if( $status === 3 )
				continue;
			$list[]	= $line;
		}
		return implode( PHP_EOL, $list );
	}
}
