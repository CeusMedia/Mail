<?php
namespace CeusMedia\MailDemo\CLI\Client;

use DOMDocument;
use DOMNode;

class HtmlToPlainText
{
	public static function convert( $html )
	{
		$doc	= new DOMDocument();
		$doc->preserveWhitespace = FALSE;
		$doc->loadHTML( $html );
		return self::convertNodes( $doc );
	}

	protected static function underline( $node, $character = '-' )
	{
		return str_repeat( $character, strlen( $node->textContent ) ).PHP_EOL;
	}

	protected static function convertNodes( DOMNode $root )
	{
		$text		= '';
		$cleared	= TRUE;
		/** @var DOMNode $node */
		foreach( $root->childNodes as $node ){
			$nodeName	= $node->nodeName;
			$nodeType	= $node->nodeType;
			$prefix		= '';
			$suffix		= '';
			if( $node->nodeType === XML_TEXT_NODE ){
				if( !$node->isWhitespaceInElementContent() )
					$text	.= wordwrap( trim( $node->textContent, "\t\r\n" ) );
			}
			else if( $node->nodeType === XML_ELEMENT_NODE ){
				if( self::isBlockElement( $node ) ){
					if( !$cleared )
						$prefix	= PHP_EOL;
					$suffix		= PHP_EOL;
					$cleared	= TRUE;
					if( in_array( $nodeName, ['h1', 'h2'] ) ){
						$prefix		.= PHP_EOL;
						$suffix		.= self::underline( $node, '=' );
					}
					else if( in_array( $nodeName, ['h3', 'h4', 'h5'] ) ){
						$prefix		.= PHP_EOL;
						$suffix		.= self::underline( $node, '-' );
					}
					else if( $nodeName == "hr" ){
						$prefix		.= str_repeat( '-', 78 );
					}
					else if( $nodeName == "li" ){
						$prefix		.= '- ';
					}
					else if( in_array( $nodeName, ["p"], TRUE ) ){
						$prefix		.= PHP_EOL;
					}
					else if( in_array( $nodeName, ["p", 'ul', 'div'], TRUE ) ){
					}
				}
				else{
					$cleared	= FALSE;
					if( $nodeName == "a" ){
						$suffix		= ' ('.$node->getAttribute( 'href' ).')';
					}
					else if( in_array( $nodeName, ["b", "strong"], TRUE ) ){
						$prefix		= '**';
						$suffix		= '**';
					}
					else if( in_array( $nodeName, ["em"], TRUE ) ){
						$prefix		= '*';
						$suffix		= '*';
					}
					else if( in_array( $nodeName, ["br"], TRUE ) ){
						$suffix		= PHP_EOL;
						$cleared	= TRUE;
					}
				}
				$inner	= '';
				if( $node->hasChildNodes() )
					$inner	= self::convertNodes( $node );
				$text	.= $prefix.$inner.$suffix;
			}
		}
		return $text;
	}

	protected static function isBlockElement( $node ): bool
	{
		$elements	= array_merge(
			['div', 'p', 'ul', 'li', 'hr', 'blockquote', 'pre', 'xmp'],
			['h1', 'h2', 'h3', 'h4', 'h5'],
		);
		return in_array( $node->nodeName, $elements, TRUE );
	}

	protected static function isInlineElement( $node )
	{
		return !self::isBlockElement( $node );
	}
}
