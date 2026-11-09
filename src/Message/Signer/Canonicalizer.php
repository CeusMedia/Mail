<?php
declare(strict_types=1);

/**
 *	DKIM message canonicalization (RFC 6376, Section 3.4).
 *
 *	Copyright (c) 2007-2025 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Signer;

use function implode;
use function preg_replace;
use function rtrim;
use function strlen;
use function strtolower;
use function trim;

/**
 *	DKIM message canonicalization (RFC 6376, Section 3.4).
 *	Supports "simple" and "relaxed" modes for both headers and body.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://datatracker.ietf.org/doc/html/rfc6376#section-3.4
 */
class Canonicalizer
{
	public const SIMPLE		= 'simple';
	public const RELAXED	= 'relaxed';

	/** @var string  $headerMode  Canonicalization mode for headers */
	protected string $headerMode;

	/** @var string  $bodyMode    Canonicalization mode for body */
	protected string $bodyMode;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$headerMode	Canonicalization mode for headers (simple|relaxed)
	 *	@param		string		$bodyMode		Canonicalization mode for body (simple|relaxed)
	 */
	public function __construct( string $headerMode = self::RELAXED, string $bodyMode = self::RELAXED )
	{
		$this->headerMode	= strtolower( $headerMode );
		$this->bodyMode		= strtolower( $bodyMode );
	}

	/**
	 *	Returns the mode identifier, e.g. "r/s" for relaxed/simple.
	 *	@access		public
	 *	@return		string
	 */
	public function getMode(): string
	{
		$header	= self::RELAXED === $this->headerMode ? 'r' : 's';
		$body	= self::RELAXED === $this->bodyMode ? 'r' : 's';
		return $header.'/'.$body;
	}

	/**
	 *	Canonicalize message headers.
	 *	Expects raw header string (no body, no trailing CRLF after the last header).
	 *	@access		public
	 *	@param		string		$headers		Raw header string
	 *	@return		string					Canonicalized headers
	 */
	public function canonicalizeHeaders( string $headers ): string
	{
		if( self::SIMPLE === $this->headerMode ){
			//  Simple: fold long lines, keep whitespace as-is, use original case
			return $this->foldHeaders( $headers );
		}

		//  Relaxed: normalize whitespace, lowercase header names
		return $this->relaxHeaders( $headers );
	}

	/**
	 *	Canonicalize message body.
	 *	@access		public
	 *	@param		string		$body		Raw body string
	 *	@return		string				Canonicalized body
	 */
	public function canonicalizeBody( string $body ): string
	{
		if( self::SIMPLE === $this->bodyMode ){
			//  Simple: convert CRLF to LF, remove trailing empty lines
			$body	= str_replace( "\r\n", "\n", $body );
			return rtrim( $body, "\n" );
		}

		//  Relaxed: normalize line endings, remove trailing whitespace, collapse empty lines
		return $this->relaxBody( $body );
	}

	/**
	 *	Canonicalize a set of headers for signing.
	 *	Parses the raw header string, selects only the specified headers, and canonicalizes them.
	 *	@access		public
	 *	@param		string		$headers		Raw header string
	 *	@param		string[]	$headerNames	List of header names to include (case-insensitive)
	 *	@return		string					Canonicalized header list (name:value\r\n)
	 */
	public function canonicalizeHeaderList( string $headers, array $headerNames ): string
	{
		//  Parse headers into name => value pairs
		$parsed	= $this->parseHeaders( $headers );

		//  Build lowercase lookup
		$lowerNames	= [];
		foreach( $headerNames as $name ){
			$lowerNames[strtolower( $name )]	= TRUE;
		}

		//  Collect and canonicalize selected headers
		$result	= [];
		foreach( $parsed as $name => $value ){
			if( isset( $lowerNames[strtolower( $name )] ) ){
				if( self::RELAXED === $this->headerMode ){
					//  Relaxed: lowercase name, collapse whitespace in value
					$name		= strtolower( $name );
					$value		= preg_replace( '/\s+/ ', ' ', trim( $value ) );
				}
				$result[]	= $name.':'.$value;
			}
		}

		return implode( "\n", $result )."\n";
	}

	//  --  PROTECTED  --  //

	/**
	 *	Fold long header lines (simple mode).
	 *	@access		protected
	 *	@param		string		$headers
	 *	@return		string
	 */
	protected function foldHeaders( string $headers ): string
	{
		//  Ensure CRLF line endings and trim trailing whitespace
		$headers	= str_replace( "\n", "\r\n", $headers );
		$headers	= rtrim( $headers, "\r\n" );
		return $headers."\r\n";
	}

	/**
	 *	Relax headers: lowercase names, normalize whitespace.
	 *	@access		protected
	 *	@param		string		$headers
	 *	@return		string
	 */
	protected function relaxHeaders( string $headers ): string
	{
		$parsed	= $this->parseHeaders( $headers );
		$result	= [];
		foreach( $parsed as $name => $value ){
			$name	= strtolower( $name );
			$value	= preg_replace( '/\s+/ ', ' ', trim( $value ) );
			$result[]	= $name.':'.$value;
		}
		return implode( "\n", $result )."\n";
	}

	/**
	 *	Relax body: normalize line endings, remove trailing whitespace, collapse empty lines.
	 *	@access		protected
	 *	@param		string		$body
	 *	@return		string
	 */
	protected function relaxBody( string $body ): string
	{
		//  Convert all line endings to CRLF
		$body	= str_replace( "\r\n", "\n", $body );
		$body	= str_replace( "\r", "\n", $body );

		//  Remove trailing whitespace from each line
		$lines	= explode( "\n", $body );
		$lines	= array_map( static fn( string $line ): string => rtrim( $line, ' \t' ), $lines );

		//  Remove trailing empty lines
		while( count( $lines ) > 0 && '' === $lines[ count( $lines ) - 1 ] ){
			array_pop( $lines );
		}

		return implode( "\n", $lines );
	}

	/**
	 *	Parse raw headers into an associative array of name => value.
	 *	Handles folded (continuation) lines.
	 *	@access		protected
	 *	@param		string		$headers
	 *	@return		array<string,string>
	 */
	protected function parseHeaders( string $headers ): array
	{
		//  Normalize line endings
		$headers	= str_replace( "\r\n", "\n", $headers );
		$headers	= rtrim( $headers, "\n" );

		$lines	= explode( "\n", $headers );

		//  Unfold continuation lines
		$unfolded	= [];
		$current	= '';
		foreach( $lines as $line ){
			if( strlen( $line ) > 0 && (' ' === $line[0] || "\t" === $line[0] ) ){
				//  Continuation line
				$current	.= ' '.trim( $line );
			}
			else{
				if( strlen( $current ) > 0 )
					$unfolded[]	= $current;
				$current	= $line;
			}
		}
		if( strlen( $current ) > 0 )
			$unfolded[]	= $current;

		//  Parse name: value pairs
		$result	= [];
		foreach( $unfolded as $line ){
			$pos	= strpos( $line, ':' );
			if( FALSE !== $pos ){
				$name	= trim( substr( $line, 0, $pos ) );
				$value	= trim( substr( $line, $pos + 1 ) );
				$result[$name]	= $value;
			}
		}

		return $result;
	}
}

