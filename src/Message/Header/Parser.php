<?php
/**
 *	Parser for mail headers.
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

use \CeusMedia\Mail\Message\Header\Section as MessageHeaderSection;
use \CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;

/**
 *	Parser for mail headers.
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			http://tools.ietf.org/html/rfc5322#section-3.3
 *	@todo			implement IMAP parser
 */
class Parser
{
	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function create(): self
	{
		return new static();
	}

	public function parse( string $content ): MessageHeaderSection
	{
		$section	= new MessageHeaderSection();
		$content	= preg_replace( "/\r?\n[\t ]/", "", $content );				//  unfold field values
		$lines		= preg_split( "/\r?\n/", $content );						//  split header fields
		foreach( $lines as $line ){
			$parts	= explode( ":", $line, 2 );
			if( count( $parts ) > 1 ){
				$value	= trim( $parts[1] );
				if( substr( $value, 0, 2 ) == "=?" )
					$value	= MessageHeaderEncoding::decodeIfNeeded( $value );
				$section->addFieldPair( $parts[0], $value );
			}
		}
		return $section;
	}
}
