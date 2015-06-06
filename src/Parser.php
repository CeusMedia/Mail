<?php
/**
 *	Mail Parser.
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;
/**
 *	Mail Parser.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2015 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			finish: parse mail headers too
 */
class Parser{

	static public function parse( $content ){
		$mail	= new \CeusMedia\Mail\Message();
		foreach( $parts as $part ){
			$mail->addPart( $part );
		}
		return $mail;
	}

	static protected function flattenParsedBodyParts( &$list, $part, $key ){
		if( $part->content !== NULL ){																//  skip if not content
			$list[$key]	= (object) array(															//  otherwise enlist part
				'headers'	=> $part->headers,
				'content'	=> $part->content,
			);
		}
		foreach( $part->nested as $subpartKey => $subpart ){										//  iterate subparts
			self::flattenParsedBodyParts( $list, $subpart, $subpartKey );							//  and flatten them too
		}
	}

	/**
	 *	Read part and return a mail part object.
	 *	@static
	 *	@access		protected
	 * 	@param		object		$part			Parsed body part data object
	 *	@param		array		$options		Map of options (mimeType, format, encoding, charset)
	 *	@return		object
	 *	@todo		finish: return an object of CMM_Mail_Part_*
	 *	@todo		implement attachments
	 */
	static protected function getPartObject( $part, $options = array() ){
		$object		= (object) array(																//  prepare body part data object
			'headers'	=> array(),																	//  ... with empty header list
			'content'	=> $part->content,															//  ... with raw content
			'body'		=> $part->content,															//  ... with body (filtered later)
			'mimeType'	=> isset( $options['mimeType'] ) ? $options['mimeType'] : 'text/plain',		//  ... with MIME type
			'format'	=> isset( $options['format'] ) ? $options['format'] : NULL,					//  ... with format
			'encoding'	=> isset( $options['encoding'] ) ? $options['encoding'] : NULL,				//  ... with encoding
			'charset'	=> isset( $options['charset'] ) ? $options['charset'] : 'UTF-8',			//  ... with charset
		);
		$headers	= array();																		//  prepare list for normalized headers
		$buffer		= "";																			//  prepare empty buffer
		foreach( $part->headers as $header ){														//  iterate part headers
			if( preg_match( "/;\s*$/", $header ) )													//  header is wrapped
				$buffer	.= $header;																	//  store header part in buffer
			else{																					//  complete header or completing line
				$headers[]	= $buffer.$header;														//  collect normalized header
				$buffer		= "";																	//  clear buffer
			}
		}
		foreach( $headers as $header ){																//  iterate normalized headers
			$headerParts	= preg_split( "/\s*:\s*/", $header, 2 );								//  split header assignment
			if( count( $headerParts ) === 2 ){														//  is header assignment
				$object->headers[$headerParts[0]]	= $headerParts[1];								//  enlist header pair
				switch( strtolower( $headerParts[0] ) ){
					case 'content-type':															//  found content type
						$valueParts	= preg_split( "/\s*;\s*/", $headerParts[1] );					//  may be several information
						$object->mimeType	= trim( array_shift( $valueParts ) );					//  first part is MIME type
						foreach( $valueParts as $valuePart ){										//  iterate additional parts
							$valuePartParts	= preg_split( "/\s*=\s*/", trim( $valuePart ), 2 );		//  split value part assignment
							if( count( $valuePartParts ) > 1 )										//  is value part assignment
								$object->$valuePartParts[0]	= $valuePartParts[1];					//  note as part parameter
						}
						break;
					case 'content-transfer-encoding':												//  found transfer encoding
						$object->encoding	= trim( $headerParts[1] );								//  note as part parameter
						break;
				}
			}

		}
		if( strtolower( $object->format === "fixed" ) )
			$object->body	= join( $object->body );
		else
			$object->body	= join( "\n", $object->body );
		if( strtolower( $object->encoding ) === "base64" )
			$object->body	= base64_decode( $object->body );

		switch( strtolower( $object->mimeType ) ){
			case 'text/html':
				$part	= new \CeusMedia\Mail\Part\HTML( $object->body, $object->charset );
				if( $object->encoding )
					$part->setEncoding( $object->encoding );
				if( $object->format )
					$part->setFormat( $object->format );
				return $part;
			case 'text/text':
			default:
				$part	= new \CeusMedia\Mail\Part\Text( $object->body, $object->charset );
				if( $object->encoding )
					$part->setEncoding( $object->encoding );
				if( $object->format )
					$part->setFormat( $object->format );
				return $part;
		}
	}

	/**
	 *	Parse mail body and return found body parts.
	 *	@static
	 *	@access		public
	 *	@param		string		$body			Mail body content
	 *	@return		array		List of found body parts
	 */
	static public function parseBody( $body ){
		$list	= array();																			//  prepare flat body parts list
		$lines	= preg_split( "/\r?\n/", $body );													//  split body lines
		$parts	= self::parseBodyPart( $lines, 1 );													//  parse body parts recursively
		self::flattenParsedBodyParts( $list, $parts, 'main' );										//  fill flat body parts list
		foreach( $list as $nr => $entry ){															//  iterate body party
			$list[$nr]	= self::getPartObject( $entry );											//  ...
		}
		return $list;																				//  return parts collected from body
	}

	/**
	 *	Parse body part and nested parts recursively.
	 *	@static
	 *	@access		proteced
	 *	@param		array		$lines			List of body part lines
	 *	@param		integer		$initialStatus	Status to start at (0: read header, 1: read content)
	 *	@return		object		Body part data object
	 */
	static protected function parseBodyPart( $lines, $initialStatus = 0 ){
		$status	= max( 0, min( 1, $initialStatus ) );												//  prepare mode within 0 and 1
		$part	= (object) array(																	//  prepare part data object
			'headers'	=> array(),																	//  ... with headers list
			'content'	=> NULL,																	//  ... and content
			'nested'	=> array(),																	//  ... and list of nested parts
		);
		foreach( $lines as $line ){																	//  iterate lines
			if( $status === 0 ){																	//  in mode: read header
				if( preg_match( "/^$/", $line ) ){													//  found empty line after headers
					$status = 1;																	//  switch to mode: read content
					continue;																		//  go to next line
				}
				$part->headers[]	= $line;														//  otherwise collect header line
			}
			else if( $status === 1 ){																//  in mode: read content
				if( preg_match( "/^--(\S+)$/", $line ) ){											//  found boundary start
					$subpartKey		= preg_replace( "/^--(\S+)$/", "\\1", $line );					//  get subpart boundary key
					$subpartLines	= array();														//  start subpart lines buffer
					$status			= 2;															//  switch to mode: read subpart
					continue;																		//  go to next line
				}
				$part->content[]	= $line;														//  otherwise collect content line
			}
			else if( $status === 2 ){																//  in mode: read subpart
				if( preg_match( "/^--".preg_quote( $subpartKey, "/" )."--$/", $line ) ){			//  found boundary end
					$part->nested[$subpartKey]	= self::parseBodyPart( $subpartLines );				//  parse nested body part
					$status		= 3;																//  switch to mode: done
					continue;																		//  go to next line
				}
				$subpartLines[]	= $line;															//  otherwise collect subpart line
			}
		}
		return $part;																				//  return this body part
	}
}
?>
