<?php
class HtmlToPlainText{

	static public function convert( $html ){
		$doc	= new DOMDocument();
		$doc->preserveWhitespace = FALSE;
		$doc->loadHTML( $html );
		return self::convertNodes( $doc );
	}

	static protected function underline( $node, $character = '-' ){
		return str_repeat( $character, strlen( $node->textContent ) ).PHP_EOL;
	}

	static protected function convertNodes( $root ){
		$text		= '';
		$cleared	= TRUE;
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
					if( in_array( $nodeName, array( 'h1', 'h2' ) ) ){
						$prefix		.= PHP_EOL;
						$suffix		.= self::underline( $node, '=' );
					}
					else if( in_array( $nodeName, array( 'h3', 'h4', 'h5' ) ) ){
						$prefix		.= PHP_EOL;
						$suffix		.= self::underline( $node, '-' );
					}
					else if( $nodeName == "hr" ){
						$prefix		.= str_repeat( '-', 78 );
					}
					else if( $nodeName == "li" ){
						$prefix		.= '- ';
					}
					else if( in_array( $nodeName, array( "p" ) ) ){
						$prefix		.= PHP_EOL;
					}
					else if( in_array( $nodeName, array( "p", 'ul', 'div' ) ) ){
					}
				}
				else{
					$cleared	= FALSE;
					if( $nodeName == "a" ){
						$suffix		= ' ('.$node->getAttribute( 'href' ).')';
					}
					else if( in_array( $nodeName, array( "b", "strong" ) ) ){
						$prefix		= '**';
						$suffix		= '**';
					}
					else if( in_array( $nodeName, array( "em" ) ) ){
						$prefix		= '*';
						$suffix		= '*';
					}
					else if( in_array( $nodeName, array( "br" ) ) ){
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

	static protected function isBlockElement( $node ){
		$elements	= array_merge(
			array( 'div', 'p', 'ul', 'li', 'hr', 'blockquote', 'pre', 'xmp' ),
			array( 'h1', 'h2', 'h3', 'h4', 'h5' )
		);
		return in_array( $node->nodeName, $elements );
	}

	static protected function isInlineElement( $node ){
		return !isBlockElement( $node );
	}
}
?>
