<?php
declare(strict_types=1);

/**
 *	Mail message header encoder and decoder.
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Header;

use CeusMedia\Mail\Message;
use CeusMedia\Mail\Util\Str;

use RangeException;

use function addcslashes;
use function array_shift;
use function base64_decode;
use function base64_encode;
use function ceil;
use function count;
use function explode;
use function iconv;
use function join;
use function mb_check_encoding;
use function mb_convert_encoding;
use function mb_str_split;
use function max;
use function min;
use function ord;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function quoted_printable_decode;
use function restore_error_handler;
use function set_error_handler;
use function rawurlencode;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strcasecmp;
use function strlen;
use function strrpos;
use function strtolower;
use function strtoupper;
use function str_replace;
use function substr;

/**
 *	Mail message header encoder and decoder.
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
 *	@package		CeusMedia_Mail_Message_Header
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Encoding
{
	/** Maximum length of lines containing encoded words (RFC 2047, 2) */
	public const LINE_LENGTH					= 76;

	/** Maximum length of an encoded word (RFC 2047, 2) */
	public const WORD_LENGTH					= 75;

	/** Pattern of a quoted string, with quoted pairs (RFC 5322, 3.2.4); possessive, so long values do not exhaust PCRE stack */
	public const PATTERN_QUOTED					= '"(?:[^"\\\\]++|\\\\.)*+"';

	/** Pattern of a header field name: printable ASCII without colon (RFC 5322, 2.2) */
	public const PATTERN_FIELD_NAME				= '[\x21-\x39\x3B-\x7E]+';

	/** Pattern of an encoded word (RFC 2047, 2) */
	public const PATTERN_ENCODED_WORD			= '=\?[^?\s]+\?[BbQq]\?[^?\s]*\?=';

	/** Pattern of a token in a phrase: quoted string, encoded word or word not starting like an encoded word */
	public const PATTERN_PHRASE_TOKEN			= self::PATTERN_QUOTED.'|'.self::PATTERN_ENCODED_WORD.'|(?!=\?)[^\s"<>@,;:()]++';

	/** Header fields containing address lists, their names are phrases (RFC 5322, 3.6.2 and 3.6.3) */
	public const ADDRESS_FIELDS					= [
		'from', 'sender', 'reply-to', 'to', 'cc', 'bcc',
		'resent-from', 'resent-sender', 'resent-to', 'resent-cc', 'resent-bcc',
		'disposition-notification-to',
	];

	/** @var		string				$charset					Target character set */
	public string $charset				= 'UTF-8';

	/**
	 *	Static constructor.
	 *	@access		public
	 *	@static
	 *	@return		self
	 */
	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 *	Decodes encoded words (RFC 2047) in a mail header value, e.g. a subject (unstructured value).
	 *	Value is unfolded. White space between adjacent encoded words is dropped (RFC 2047, 6.2).
	 *	Octets of adjacent words are joined before conversion, so a character split across words stays intact.
	 *	Independent of PHP version and iconv implementation, tolerant to malformed words, which stay as they are.
	 *	For address lists, see decodeAddressListIfNeeded().
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@return		string
	 */
	public function decodeIfNeeded( string $string ): string
	{
		$string	= self::unfold( $string );
		if( !str_contains( $string, '=?' ) )
			return $string;
		$run		= '(?>'.self::PATTERN_ENCODED_WORD.')(?>\s+(?>'.self::PATTERN_ENCODED_WORD.'))*';
		$decoded	= preg_replace_callback( '/'.$run.'/', function( array $matches ): string {
			preg_match_all( '/'.self::PATTERN_ENCODED_WORD.'/', $matches[0], $words );
			return $this->decodeWords( $words[0] );
		}, $string );
		return $decoded ?? $string;														//  regex failure: keep value
	}

	/**
	 *	Converts octets of given charset to charset of this encoder.
	 *	Unknown charset: valid UTF-8 kept, else read as Latin-1.
	 *	@access		public
	 *	@param		string		$octets			Octets to convert
	 *	@param		string		$charset		Charset of octets
	 *	@return		string
	 */
	public function convertToCharset( string $octets, string $charset ): string
	{
		if( 0 === strcasecmp( $charset, $this->charset ) )
			return $octets;
		set_error_handler( static fn(): bool => TRUE, E_WARNING | E_NOTICE );			//  unknown charset: no warning, also for custom error handlers
		try{
			$converted	= iconv( $charset, $this->charset, $octets );
		}
		finally{
			restore_error_handler();
		}
		if( FALSE !== $converted )
			return $converted;
		if( mb_check_encoding( $octets, 'UTF-8' ) )
			return 0 === strcasecmp( $this->charset, 'UTF-8' ) ? $octets : (string) mb_convert_encoding( $octets, $this->charset, 'UTF-8' );
		return (string) mb_convert_encoding( $octets, $this->charset, 'ISO-8859-1' );
	}

	/**
	 *	Decodes encoded words in an address list header value (From, To, Cc, Reply-To, ...).
	 *	Unlike decodeIfNeeded() on the whole value, decoded names containing special characters are quoted,
	 *	so the list can still be split: "=?UTF-8?B?…?= <a@example.org>" for "Doe, Jürgen" becomes
	 *	"\"Doe, Jürgen\" <a@example.org>" instead of two addresses.
	 *	@access		public
	 *	@param		string		$string			Address list header value, may be folded
	 *	@return		string
	 */
	public function decodeAddressListIfNeeded( string $string ): string
	{
		$string	= self::unfold( $string );
		if( !str_contains( $string, '=?' ) )
			return $string;
		//  atomic groups, possessive quantifiers and distinct branches: linear, no catastrophic backtracking, no PCRE stack limit
		$phrase		= '(?>'.self::PATTERN_PHRASE_TOKEN.')(?>[ \t]+(?>'.self::PATTERN_PHRASE_TOKEN.'))*+';
		$decoded	= preg_replace_callback( '/'.$phrase.'(?=[ \t]*<)/', function( array $matches ): string {	//  name before angle address
			return $this->decodePhrase( $matches[0] );
		}, $string );
		if( NULL === $decoded )															//  regex failure: keep value
			return $string;
		$decoded	= preg_replace_callback( '/\((?>[^()\\\\]++|\\\\.)*+\)/', function( array $matches ): string {	//  comments (RFC 2047, 5 (2))
			if( !str_contains( $matches[0], '=?' ) )
				return $matches[0];
			$content	= (string) preg_replace( '/\\\\(.)/s', '$1', substr( $matches[0], 1, -1 ) );
			return '('.addcslashes( $this->decodeIfNeeded( $content ), '()\\' ).')';
		}, $decoded );
		return $decoded ?? $string;
	}

	/**
	 *	Encodes a mail header value string if needed, e.g. a subject (unstructured value).
	 *	Printable ASCII stays plain, only folded at spaces if too long.
	 *	Otherwise, the whole value is encoded as RFC 2047 encoded words, see encodeWords().
	 *	For names in address lists (phrases), see Address\Collection\Renderer::setEncodeNames().
	 *	@access		public
	 *	@param		string		$string			A mail header value string, subject for example.
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable
	 *	@param		bool		$fold			Flag: apply folding, default: yes
	 *	@param		int			$offset			Length of header name and colon and space, e.g. 9 for "Subject: "
	 *	@return		string
	 *	@throws		RangeException				if encoding is not supported
	 */
	public function encodeIfNeeded( string $string, string $encoding = 'base64', bool $fold = TRUE, int $offset = 0 ): string
	{
		if( self::isPrintableAscii( $string ) && !str_contains( $string, '=?' ) )		//  no line breaks (header injection)
			return $fold ? $this->foldPlain( $string, $offset ) : $string;
		return $this->encodeWords( $string, $encoding, $fold, $offset );
	}

	/**
	 *	Encodes the whole string as RFC 2047 encoded words, independent of PHP version.
	 *	Each encoded word contains whole characters only and is at most 75 characters long (RFC 2047, 2 and 5).
	 *	Quoted-printable (Q) encodes all characters except letters, digits and !*+-/,
	 *	so the result is also valid as phrase, e.g. a name in an address list (RFC 2047, 5 (3)).
	 *	@access		public
	 *	@param		string		$string			String to encode, in charset of this encoder (UTF-8)
	 *	@param		string		$encoding		Optional: base64 (default) or quoted-printable
	 *	@param		bool		$fold			Flag: put each encoded word on a new line, default: yes
	 *	@param		int			$offset			Length of header name and colon and space, e.g. 9 for "Subject: "
	 *	@return		string
	 *	@throws		RangeException				if encoding is not supported
	 */
	public function encodeWords( string $string, string $encoding = 'base64', bool $fold = TRUE, int $offset = 0 ): string
	{
		$type		= $this->getEncodingType( $encoding );
		$prefix		= '=?'.$this->charset.'?'.$type.'?';
		$overhead	= strlen( $prefix ) + 2;
		$budget		= min( self::WORD_LENGTH, self::LINE_LENGTH - $offset ) - $overhead;
		$nextBudget	= min( self::WORD_LENGTH, self::LINE_LENGTH - 1 ) - $overhead;		//  next line starts with a space
		$characters	= mb_str_split( $string, 1, $this->charset );
		$lead		= '';
		$first		= $characters[0] ?? '';
		$firstSize	= 'B' === $type ? 4 * (int) ceil( strlen( $first ) / 3 ) : strlen( $this->encodeWordContent( $first, $type ) );
		if( $fold && $firstSize > $budget ){											//  not even one character fits: start on next line
			$lead	= Message::$delimiter.' ';
			$budget	= $nextBudget;
		}
		$words		= [];
		$chunk		= '';
		$length		= 0;															//  bytes for B, encoded length for Q
		foreach( $characters as $character ){
			$added	= 'B' === $type ? strlen( $character ) : strlen( $this->encodeWordContent( $character, $type ) );
			$size	= 'B' === $type ? 4 * (int) ceil( ( $length + $added ) / 3 ) : $length + $added;
			if( '' !== $chunk && $size > $budget ){
				$words[]	= $prefix.$this->encodeWordContent( $chunk, $type ).'?=';
				$chunk		= '';
				$length		= 0;
				$budget		= $nextBudget;
			}
			$chunk	.= $character;
			$length	+= $added;
		}
		$words[]	= $prefix.$this->encodeWordContent( $chunk, $type ).'?=';
		return $lead.join( $fold ? Message::$delimiter.' ' : ' ', $words );
	}

	/**
	 *	Indicates whether string contains printable ASCII and tabs only, no line breaks, so no encoding is needed.
	 *	@access		public
	 *	@static
	 *	@param		string		$string			String to check
	 *	@return		bool
	 */
	public static function isPrintableAscii( string $string ): bool
	{
		return 1 === preg_match( '/^[\x20-\x7E\t]*$/', $string );
	}

	/**
	 *	Returns string as quoted string, quotes and backslashes escaped (RFC 5322, 3.2.4).
	 *	@access		public
	 *	@static
	 *	@param		string		$string			String to quote
	 *	@return		string
	 */
	public static function quote( string $string ): string
	{
		return '"'.addcslashes( $string, '"\\' ).'"';
	}

	/**
	 *	Returns content of quoted string, quoted pairs resolved (RFC 5322, 3.2.4).
	 *	A string without surrounding quotes is returned unchanged.
	 *	@access		public
	 *	@static
	 *	@param		string		$string			Quoted string
	 *	@return		string
	 */
	public static function unquote( string $string ): string
	{
		if( strlen( $string ) < 2 || !str_starts_with( $string, '"' ) || !str_ends_with( $string, '"' ) )
			return $string;
		return (string) preg_replace( '/\\\\(.)/s', '$1', substr( $string, 1, -1 ) );
	}

	/**
	 *	Returns length of last line of a maybe folded header value.
	 *	@access		public
	 *	@static
	 *	@param		string		$string			Header value, may be folded
	 *	@param		integer		$offset			Position of value in first line, added if not folded
	 *	@return		integer
	 */
	public static function getLastLineLength( string $string, int $offset = 0 ): int
	{
		$position	= strrpos( $string, Message::$delimiter );
		if( FALSE === $position )
			return $offset + strlen( $string );
		return strlen( $string ) - $position - strlen( Message::$delimiter );
	}

	/**
	 *	Renders header parameter, like filename="test.txt" (RFC 2045, 5.1).
	 *	Printable ASCII is quoted and escaped, also if long (e.g. a MIME boundary).
	 *	Other values, like non-ASCII file names, are percent-encoded with charset (RFC 2231, 4):
	 *	filename*=UTF-8''Gr%C3%BC%C3%9Fe.txt. Long ones are split into continuations, one per line (RFC 2231, 3):
	 *	filename*0*=…; filename*1*=…
	 *	@access		public
	 *	@static
	 *	@param		string		$name			Parameter name
	 *	@param		string		$value			Parameter value, UTF-8
	 *	@return		string
	 */
	public static function renderParameter( string $name, string $value ): string
	{
		if( self::isPrintableAscii( $value ) )
			return $name.'='.self::quote( $value );

		$maxLength	= self::LINE_LENGTH - 2;											//  folded line: space, parameter, semicolon
		$encoded	= "UTF-8''".rawurlencode( $value );
		if( strlen( $name ) + 2 + strlen( $encoded ) <= $maxLength )
			return $name.'*='.$encoded;

		$segments	= [];
		for( $index = 0; '' !== $encoded; $index++ ){
			$prefix		= $name.'*'.$index.'*=';
			$length		= max( 3, $maxLength - strlen( $prefix ) );
			$segment	= substr( $encoded, 0, $length );
			$position	= strrpos( substr( $segment, -2 ), '%' );						//  do not split %XX
			if( FALSE !== $position )
				$segment	= substr( $segment, 0, strlen( $segment ) - 2 + $position );
			$segments[]	= $prefix.$segment;
			$encoded	= substr( $encoded, strlen( $segment ) );
		}
		return join( ';'.Message::$delimiter.' ', $segments );
	}

	/**
	 *	Unfolds header value: removes line breaks followed by white space (RFC 5322, 2.2.3).
	 *	@access		public
	 *	@static
	 *	@param		string		$string			Header value, may be folded
	 *	@return		string
	 */
	public static function unfold( string $string ): string
	{
		return (string) preg_replace( '/\r?\n(?=[ \t])/', '', $string );
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Decodes a name (phrase) of encoded words, words and quoted strings, as a whole.
	 *	Quoted if containing special characters, so a decoded comma does not split an address list.
	 *	White space between adjacent encoded words is dropped (RFC 2047, 6.2).
	 *	@access		protected
	 *	@param		string		$phrase			Phrase, i.e. name before angle address
	 *	@return		string
	 */
	protected function decodePhrase( string $phrase ): string
	{
		if( !str_contains( $phrase, '=?' ) )
			return $phrase;
		preg_match_all( '/'.self::PATTERN_PHRASE_TOKEN.'/', $phrase, $matches );
		$texts	= [];
		$words	= [];																//  run of adjacent encoded words, decoded together
		foreach( $matches[0] as $part ){
			if( 1 === preg_match( '/^'.self::PATTERN_ENCODED_WORD.'$/', $part ) ){
				$words[]	= $part;
				continue;
			}
			if( 0 !== count( $words ) )
				$texts[]	= $this->decodeWords( $words );
			$words	= [];
			if( str_starts_with( $part, '"' ) ){
				$part	= self::unquote( $part );
				if( str_contains( $part, '=?' ) )									//  encoded words in quoted string: not allowed, but common
					$part	= $this->decodeIfNeeded( $part );
			}
			$texts[]	= $part;
		}
		if( 0 !== count( $words ) )
			$texts[]	= $this->decodeWords( $words );
		$result	= join( ' ', $texts );
		if( 1 === preg_match( '/[()<>\[\]:;@\\\\,."]/', $result ) )					//  special characters (RFC 5322, 3.2.3)
			return self::quote( $result );
		return $result;
	}

	/**
	 *	Decodes run of adjacent encoded words. Space between them is dropped (RFC 2047, 6.2).
	 *	Decoded line breaks become a space.
	 *	Octets of words with same charset are joined before conversion,
	 *	so a character split across words (sent by some mailers) stays intact.
	 *	@access		protected
	 *	@param		array<string>	$words			Encoded words
	 *	@return		string
	 */
	protected function decodeWords( array $words ): string
	{
		$result		= '';
		$charset	= NULL;
		$octets		= '';
		foreach( $words as $word ){
			if( 1 !== preg_match( '/^=\?([^?*]+)(?:\*[^?]*)?\?([BbQq])\?([^?]*)\?=$/', $word, $parts ) ){
				if( NULL !== $charset )													//  flush octets of words before, keeping order
					$result	.= $this->convertToCharset( $octets, $charset );
				$charset	= NULL;
				$octets		= '';
				$result		.= $word;												//  unreadable word: kept
				continue;
			}
			$content	= 'B' === strtoupper( $parts[2] )
				? (string) base64_decode( $parts[3] )
				: quoted_printable_decode( str_replace( '_', ' ', $parts[3] ) );
			if( NULL !== $charset && 0 !== strcasecmp( $charset, $parts[1] ) ){
				$result	.= $this->convertToCharset( $octets, $charset );
				$octets	= '';
			}
			$charset	= $parts[1];
			$octets		.= $content;
		}
		if( NULL !== $charset )
			$result	.= $this->convertToCharset( $octets, $charset );
		//  encoded text is text without line breaks (RFC 2047, 5): decoded ones would break the header (injection)
		return (string) preg_replace( '/[\r\n]+/', ' ', $result );
	}

	/**
	 *	Encodes content of an encoded word.
	 *	@access		protected
	 *	@param		string		$string			Whole characters to encode
	 *	@param		string		$type			B or Q
	 *	@return		string
	 */
	protected function encodeWordContent( string $string, string $type ): string
	{
		if( 'B' === $type )
			return base64_encode( $string );
		return (string) preg_replace_callback( '/[^A-Za-z0-9!*+\/-]/', static function( array $matches ): string {
			return ' ' === $matches[0] ? '_' : sprintf( '=%02X', ord( $matches[0] ) );
		}, $string );
	}

	/**
	 *	Folds printable ASCII at spaces, if longer than line length.
	 *	A single word longer than line length stays unbroken.
	 *	@access		protected
	 *	@param		string		$string			Printable ASCII string
	 *	@param		int			$offset			Length of header name and colon and space
	 *	@return		string
	 */
	protected function foldPlain( string $string, int $offset ): string
	{
		if( $offset + strlen( $string ) <= self::LINE_LENGTH )
			return $string;
		$words		= explode( ' ', $string );
		$result		= (string) array_shift( $words );
		$length		= $offset + strlen( $result );
		$hasText	= Str::hasContent( $result );
		foreach( $words as $word ){
			if( $hasText && '' !== $word && $length + 1 + strlen( $word ) > self::LINE_LENGTH ){
				$result	.= Message::$delimiter.' '.$word;
				$length	= 1 + strlen( $word );
			}
			else{
				$result	.= ' '.$word;
				$length	+= 1 + strlen( $word );
			}
			$hasText	= $hasText || '' !== $word;
		}
		return $result;
	}

	/**
	 *	Returns RFC 2047 encoding type for encoding name.
	 *	@access		protected
	 *	@param		string		$encoding		base64, b, quoted-printable or q
	 *	@return		string		B or Q
	 *	@throws		RangeException				if encoding is not supported
	 */
	protected function getEncodingType( string $encoding ): string
	{
		return match( strtolower( $encoding ) ){
			'b', 'base64'				=> 'B',
			'q', 'quoted-printable'		=> 'Q',
			default						=> throw new RangeException( 'Unsupported encoding: '.$encoding ),
		};
	}
}
