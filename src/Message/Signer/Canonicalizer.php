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

use InvalidArgumentException;

use function array_pop;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_replace;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 *	DKIM message canonicalization (RFC 6376, Section 3.4).
 *	Supports "simple" and "relaxed" modes for headers and body.
 *
 *	All results use CRLF line endings, as transmitted by SMTP, also for input with LF line endings.
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

	public const MODES		= [
		self::SIMPLE,
		self::RELAXED,
	];

	/** @var string  $headerMode  Canonicalization mode for headers */
	protected string $headerMode;

	/** @var string  $bodyMode    Canonicalization mode for body */
	protected string $bodyMode;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$headerMode		Canonicalization mode for headers (simple|relaxed)
	 *	@param		string		$bodyMode		Canonicalization mode for body (simple|relaxed)
	 *	@throws		InvalidArgumentException	if a mode is unknown
	 */
	public function __construct( string $headerMode = self::RELAXED, string $bodyMode = self::RELAXED )
	{
		$this->headerMode	= self::checkMode( $headerMode );
		$this->bodyMode		= self::checkMode( $bodyMode );
	}

	/**
	 *	Returns the value of the c= tag, e.g. "relaxed/simple".
	 *	@access		public
	 *	@return		string
	 */
	public function getMode(): string
	{
		return $this->headerMode.'/'.$this->bodyMode;
	}

	/**
	 *	Canonicalize message body (RFC 6376, 3.4.3 and 3.4.4).
	 *	Simple: trailing empty lines removed, ends with one CRLF (empty body: CRLF).
	 *	Relaxed: whitespace at line ends removed, other whitespace runs reduced to one space,
	 *	trailing empty lines removed, ends with one CRLF (empty body: empty string).
	 *	@access		public
	 *	@param		string		$body		Raw body string
	 *	@return		string					Canonicalized body
	 */
	public function canonicalizeBody( string $body ): string
	{
		$lines	= explode( "\n", str_replace( "\r\n", "\n", $body ) );
		if( self::RELAXED === $this->bodyMode ){
			foreach( $lines as $nr => $line )
				$lines[$nr]	= rtrim( (string) preg_replace( '/[ \t]+/', ' ', $line ), ' ' );
		}
		while( 0 !== count( $lines ) && '' === $lines[count( $lines ) - 1] )
			array_pop( $lines );

		if( 0 === count( $lines ) )
			return self::RELAXED === $this->bodyMode ? '' : "\r\n";
		return implode( "\r\n", $lines )."\r\n";
	}

	/**
	 *	Canonicalize one header field (RFC 6376, 3.4.1 and 3.4.2), without trailing CRLF.
	 *	Simple: unchanged, folding kept (line endings as CRLF).
	 *	Relaxed: name in lower case, unfolded, whitespace runs reduced to one space,
	 *	no whitespace around the colon and at the end.
	 *	@access		public
	 *	@param		string		$field		Raw header field (name: value), may be folded
	 *	@return		string
	 */
	public function canonicalizeHeaderField( string $field ): string
	{
		$field	= rtrim( str_replace( "\r\n", "\n", $field ), "\n" );
		if( self::SIMPLE === $this->headerMode )
			return str_replace( "\n", "\r\n", $field );

		$pos	= strpos( $field, ':' );
		if( FALSE === $pos )
			return strtolower( trim( $field ) );
		$name	= strtolower( rtrim( substr( $field, 0, $pos ), " \t" ) );
		$value	= str_replace( "\n", '', substr( $field, $pos + 1 ) );
		$value	= trim( (string) preg_replace( '/[ \t]+/', ' ', $value ), ' ' );
		return $name.':'.$value;
	}

	/**
	 *	Canonicalize all fields of a header section, each ending with CRLF.
	 *	@access		public
	 *	@param		string		$headers		Raw header section
	 *	@return		string
	 */
	public function canonicalizeHeaders( string $headers ): string
	{
		$result	= '';
		foreach( self::splitHeaderFields( $headers ) as $field )
			$result	.= $this->canonicalizeHeaderField( $field )."\r\n";
		return $result;
	}

	/**
	 *	Canonicalize header fields to sign, in order of the given names, each ending with CRLF (RFC 6376, 5.4.2).
	 *	A name given several times takes the next instance from the bottom of the header section.
	 *	Names without (another) instance add nothing, so listing a name once more prevents adding it later (oversigning).
	 *	@access		public
	 *	@param		string		$headers		Raw header section
	 *	@param		string[]	$headerNames	Names of header fields to sign (case-insensitive), as in h= tag
	 *	@return		string
	 */
	public function canonicalizeHeaderList( string $headers, array $headerNames ): string
	{
		$fields	= self::splitHeaderFields( $headers );
		$used	= [];
		$result	= '';
		foreach( $headerNames as $headerName ){
			$headerName	= strtolower( trim( $headerName ) );
			for( $i = count( $fields ) - 1; $i >= 0; $i-- ){
				if( in_array( $i, $used, TRUE ) || $headerName !== self::getFieldName( $fields[$i] ) )
					continue;
				$used[]	= $i;
				$result	.= $this->canonicalizeHeaderField( $fields[$i] )."\r\n";
				break;
			}
		}
		return $result;
	}

	/**
	 *	Returns the name of a raw header field in lower case.
	 *	@access		public
	 *	@static
	 *	@param		string		$field		Raw header field
	 *	@return		string
	 */
	public static function getFieldName( string $field ): string
	{
		$pos	= strpos( $field, ':' );
		return strtolower( trim( FALSE === $pos ? $field : substr( $field, 0, $pos ) ) );
	}

	/**
	 *	Splits a header section into raw fields, folding kept (line endings as in input, without trailing line break).
	 *	@access		public
	 *	@static
	 *	@param		string		$headers		Raw header section, lines separated by CRLF or LF
	 *	@return		array<int,string>
	 */
	public static function splitHeaderFields( string $headers ): array
	{
		$eol	= FALSE !== strpos( $headers, "\r\n" ) ? "\r\n" : "\n";
		$fields	= [];
		foreach( explode( $eol, rtrim( $headers, "\r\n" ) ) as $line ){
			if( 0 !== strlen( $line ) && in_array( $line[0], [' ', "\t"], TRUE ) ){
				if( 0 !== count( $fields ) )
					$fields[count( $fields ) - 1]	.= $eol.$line;
				continue;
			}
			if( '' !== $line )
				$fields[]	= $line;
		}
		return $fields;
	}

	//  --  PROTECTED  --  //

	/**
	 *	@param		string		$mode
	 *	@return		string
	 *	@throws		InvalidArgumentException	if mode is unknown
	 */
	protected static function checkMode( string $mode ): string
	{
		$mode	= strtolower( trim( $mode ) );
		if( !in_array( $mode, self::MODES, TRUE ) )
			throw new InvalidArgumentException( 'Invalid canonicalization mode "'.$mode.'", must be simple or relaxed' );
		return $mode;
	}
}
