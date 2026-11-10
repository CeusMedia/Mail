<?php
declare(strict_types=1);

/**
 *	DKIM message signer (RFC 6376).
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
use RuntimeException;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function base64_encode;
use function count;
use function file_get_contents;
use function hash;
use function implode;
use function in_array;
use function is_readable;
use function is_string;
use function max;
use function openssl_pkey_get_details;
use function openssl_pkey_get_private;
use function openssl_sign;
use function preg_match;
use function preg_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function time;
use function trim;

/**
 *	DKIM message signer (RFC 6376, RSA-SHA256).
 *	Prepends a DKIM-Signature header field to a rendered message, the message itself stays unchanged.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://datatracker.ietf.org/doc/html/rfc6376
 */
class Dkim
{
	public const ALGORITHM_RSA_SHA256	= 'rsa-sha256';

	public const ALGORITHMS				= [
		self::ALGORITHM_RSA_SHA256,
	];

	/** @var string[]  Header fields signed by default, if present (RFC 6376, 5.4.1) */
	public const DEFAULT_HEADERS		= [
		'from', 'reply-to', 'subject', 'date', 'to', 'cc', 'message-id',
		'in-reply-to', 'references', 'mime-version', 'content-type',
	];

	/** @var string[]  Header fields signed once more than present by default, so no other instance can be added */
	public const DEFAULT_OVERSIGN		= [ 'from', 'subject' ];

	/** @var string[]  Header fields never to sign: Bcc is removed on the way, the signature would break */
	public const FORBIDDEN_HEADERS		= [ 'bcc', 'dkim-signature' ];

	/** @var int  Minimum RSA key size in bits (RFC 8301) */
	public const MIN_KEY_BITS			= 1024;

	/** @var int  Maximum line length of the DKIM-Signature header field */
	public const LINE_LENGTH			= 78;

	/** @var mixed  $privateKey  OpenSSL private key (OpenSSLAsymmetricKey) */
	protected $privateKey	= NULL;

	/** @var string  $domain       DKIM signing domain (d=) */
	protected string $domain;

	/** @var string  $selector     DKIM key selector (s=) */
	protected string $selector;

	/** @var string  $algorithm    DKIM algorithm (a=) */
	protected string $algorithm	= self::ALGORITHM_RSA_SHA256;

	/** @var string[]  $headers    Header fields to sign, if present */
	protected array $headers	= self::DEFAULT_HEADERS;

	/** @var string[]  $oversign   Header fields to sign once more than present */
	protected array $oversign	= self::DEFAULT_OVERSIGN;

	/** @var bool  $allow8bit  Flag: sign messages with 8bit data (non-ASCII bytes) */
	protected bool $allow8bit	= FALSE;

	/** @var int|NULL  $timestamp  Signature timestamp (t=), NULL: current time */
	protected ?int $timestamp	= NULL;

	/** @var Canonicalizer  $canonicalizer  */
	protected Canonicalizer $canonicalizer;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$privateKeyPath	Path to PEM-encoded private key file
	 *	@param		string		$domain			DKIM signing domain, should match the domain of From (DMARC alignment)
	 *	@param		string		$selector		DKIM key selector (DNS record <selector>._domainkey.<domain>)
	 *	@param		string		$headerMode		Canonicalization mode for headers (simple|relaxed)
	 *	@param		string		$bodyMode		Canonicalization mode for body (simple|relaxed)
	 *	@throws		InvalidArgumentException	if private key file is not readable or domain or selector are empty
	 *	@throws		RuntimeException			if private key cannot be parsed
	 */
	public function __construct(
		string $privateKeyPath,
		string $domain,
		string $selector,
		string $headerMode	= Canonicalizer::RELAXED,
		string $bodyMode	= Canonicalizer::RELAXED
	)
	{
		$domain		= trim( $domain );
		$selector	= trim( $selector );
		if( '' === $domain || 1 === preg_match( '/[\s;=]/', $domain ) )
			throw new InvalidArgumentException( 'Invalid DKIM domain "'.$domain.'"' );
		if( '' === $selector || 1 === preg_match( '/[\s;=]/', $selector ) )
			throw new InvalidArgumentException( 'Invalid DKIM selector "'.$selector.'"' );
		$this->domain			= strtolower( $domain );
		$this->selector			= $selector;
		$this->canonicalizer	= new Canonicalizer( $headerMode, $bodyMode );

		$this->loadPrivateKey( $privateKeyPath );
	}

	/**
	 *	Constructor with raw private key string.
	 *	@access		public
	 *	@static
	 *	@param		string		$privateKey		PEM-encoded private key string
	 *	@param		string		$domain			DKIM signing domain
	 *	@param		string		$selector		DKIM key selector
	 *	@param		string		$headerMode		Canonicalization mode for headers
	 *	@param		string		$bodyMode		Canonicalization mode for body
	 *	@return		self
	 *	@throws		InvalidArgumentException	if domain or selector are invalid
	 *	@throws		RuntimeException			if private key cannot be parsed
	 */
	public static function createFromKey(
		string $privateKey,
		string $domain,
		string $selector,
		string $headerMode	= Canonicalizer::RELAXED,
		string $bodyMode	= Canonicalizer::RELAXED
	): self
	{
		$dkim	= new self( '', $domain, $selector, $headerMode, $bodyMode );
		$dkim->setPrivateKeyFromString( $privateKey );
		return $dkim;
	}

	/**
	 *	Returns the value of the DNS TXT record for <selector>._domainkey.<domain>.
	 *	@access		public
	 *	@return		string		e.g. "v=DKIM1; k=rsa; p=MIIBIjANBg..."
	 *	@throws		RuntimeException	if no private key is loaded
	 */
	public function getDnsRecord(): string
	{
		if( NULL === $this->privateKey )
			throw new RuntimeException( 'No private key loaded for DKIM signing' );
		$details	= openssl_pkey_get_details( $this->privateKey );
		if( FALSE === $details || !is_string( $details['key'] ?? NULL ) )
			throw new RuntimeException( 'Cannot read public key of private key' );
		$publicKey	= (string) preg_replace( '/-----[A-Z ]+-----|\s+/', '', $details['key'] );
		return 'v=DKIM1; k=rsa; p='.$publicKey;
	}

	/**
	 *	Returns the DNS name of the key record.
	 *	@access		public
	 *	@return		string		e.g. "default._domainkey.example.com"
	 */
	public function getDnsName(): string
	{
		return $this->selector.'._domainkey.'.$this->domain;
	}

	/**
	 *	Allows to sign messages containing 8bit data (non-ASCII bytes), e.g. parts with 8bit transfer encoding.
	 *	Disallowed by default: a server without 8BITMIME on the way converts the content to 7bit,
	 *	which breaks the signature. Allow only if all servers on the way support 8BITMIME,
	 *	else use base64 (default) or quoted-printable for parts.
	 *	@access		public
	 *	@param		bool		$allow		Flag: allow 8bit data
	 *	@return		self
	 */
	public function setAllow8bit( bool $allow ): self
	{
		$this->allow8bit	= $allow;
		return $this;
	}

	/**
	 *	Sets the DKIM algorithm.
	 *	@access		public
	 *	@param		string		$algorithm		Algorithm identifier, only rsa-sha256 supported
	 *	@return		self
	 *	@throws		InvalidArgumentException	if algorithm is not supported
	 */
	public function setAlgorithm( string $algorithm ): self
	{
		$algorithm	= strtolower( trim( $algorithm ) );
		if( !in_array( $algorithm, self::ALGORITHMS, TRUE ) )
			throw new InvalidArgumentException( 'Unsupported DKIM algorithm "'.$algorithm.'"' );
		$this->algorithm	= $algorithm;
		return $this;
	}

	/**
	 *	Sets the header fields to sign, if present in message. From is required.
	 *	@access		public
	 *	@param		string[]	$headers		Header names (case-insensitive)
	 *	@return		self
	 *	@throws		InvalidArgumentException	if From is missing or a forbidden header (Bcc, DKIM-Signature) is given
	 */
	public function setHeaders( array $headers ): self
	{
		$headers	= self::normalizeHeaderNames( $headers );
		if( !in_array( 'from', $headers, TRUE ) )
			throw new InvalidArgumentException( 'Header From must be signed (RFC 6376, 5.4)' );
		$this->headers	= $headers;
		return $this;
	}

	/**
	 *	Sets the header fields to sign once more than present (oversigning),
	 *	so that no other instance can be added later without breaking the signature.
	 *	@access		public
	 *	@param		string[]	$headers		Header names (case-insensitive), empty to disable
	 *	@return		self
	 *	@throws		InvalidArgumentException	if a forbidden header (Bcc, DKIM-Signature) is given
	 */
	public function setOversignHeaders( array $headers ): self
	{
		$this->oversign	= self::normalizeHeaderNames( $headers );
		return $this;
	}

	/**
	 *	Sets a fixed signature timestamp (t=), e.g. for tests.
	 *	@access		public
	 *	@param		int|NULL	$timestamp		UNIX timestamp, NULL: time of signing
	 *	@return		self
	 */
	public function setTimestamp( ?int $timestamp ): self
	{
		$this->timestamp	= $timestamp;
		return $this;
	}

	/**
	 *	Sign a rendered message and return it with a DKIM-Signature header field prepended.
	 *	Line endings of the message (CRLF or LF) are kept, the signature is computed for CRLF, as sent by SMTP.
	 *	@access		public
	 *	@param		string		$message		Full rendered message (headers and body, separated by empty line)
	 *	@return		string						Message with DKIM-Signature header added
	 *	@throws		RuntimeException			if message has no From header, contains 8bit data (unless allowed) or signing fails
	 */
	public function sign( string $message ): string
	{
		if( !$this->allow8bit && 1 === preg_match( '/[\x80-\xFF]/', $message ) )
			throw new RuntimeException( 'Message contains 8bit data, a conversion to 7bit on the way would break the DKIM signature: use base64 or quoted-printable for parts, or allow by setAllow8bit' );
		$eol		= FALSE !== strpos( $message, "\r\n" ) ? "\r\n" : "\n";
		$pos		= strpos( $message, $eol.$eol );
		$headers	= FALSE === $pos ? $message : substr( $message, 0, $pos );
		$body		= FALSE === $pos ? '' : substr( $message, $pos + 2 * strlen( $eol ) );

		$signedHeaders	= $this->getHeaderNamesToSign( $headers );

		$tags	= [
			'v'		=> '1',
			'a'		=> $this->algorithm,
			'c'		=> $this->canonicalizer->getMode(),
			'd'		=> $this->domain,
			's'		=> $this->selector,
			't'		=> (string) ( $this->timestamp ?? time() ),
			'bh'	=> base64_encode( hash( 'sha256', $this->canonicalizer->canonicalizeBody( $body ), TRUE ) ),
		];

		//  header field with empty signature (b=), signed itself as last field without CRLF (RFC 6376, 3.7)
		$field	= $this->renderField( $tags, $signedHeaders, $eol );
		$data	= $this->canonicalizer->canonicalizeHeaderList( $headers, $signedHeaders );
		$data	.= $this->canonicalizer->canonicalizeHeaderField( $field );

		$field	.= $this->foldSignature( base64_encode( $this->signString( $data ) ), $field, $eol );
		return $field.$eol.$message;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Folds the signature value to append it to the header field ending with "b=".
	 *	@access		protected
	 *	@param		string		$signature		Base64 encoded signature
	 *	@param		string		$field			Header field so far
	 *	@param		string		$eol			Line ending
	 *	@return		string
	 */
	protected function foldSignature( string $signature, string $field, string $eol ): string
	{
		$lastLine	= substr( $field, (int) strrpos( "\n".$field, "\n" ) );
		$length		= max( 0, self::LINE_LENGTH - strlen( $lastLine ) );
		$result		= substr( $signature, 0, $length );
		for( $i = $length; $i < strlen( $signature ); $i += self::LINE_LENGTH - 1 )
			$result	.= $eol.' '.substr( $signature, $i, self::LINE_LENGTH - 1 );
		return $result;
	}

	/**
	 *	Returns names for the h= tag: each configured name once per instance in the message,
	 *	oversigned names once more.
	 *	@access		protected
	 *	@param		string		$headers		Raw header section
	 *	@return		string[]
	 *	@throws		RuntimeException			if message has no From header
	 */
	protected function getHeaderNamesToSign( string $headers ): array
	{
		$present	= array_map(
			static fn( string $field ): string => Canonicalizer::getFieldName( $field ),
			Canonicalizer::splitHeaderFields( $headers )
		);
		if( !in_array( 'from', $present, TRUE ) )
			throw new RuntimeException( 'Message has no From header, cannot be signed by DKIM' );

		$names	= [];
		foreach( array_unique( array_merge( $this->headers, $this->oversign ) ) as $name ){
			foreach( $present as $presentName )
				if( $presentName === $name )
					$names[]	= $name;
			if( in_array( $name, $this->oversign, TRUE ) )
				$names[]	= $name;
		}
		return $names;
	}

	/**
	 *	Load a private key from a PEM file.
	 *	@access		protected
	 *	@param		string		$path		Path to PEM file, empty: key will be set by setPrivateKeyFromString
	 *	@throws		InvalidArgumentException	if file not readable
	 *	@throws		RuntimeException			if key cannot be parsed
	 */
	protected function loadPrivateKey( string $path ): void
	{
		if( '' === $path )
			return;

		if( !is_readable( $path ) )
			throw new InvalidArgumentException( 'Private key file "'.$path.'" is not readable' );

		$pem	= file_get_contents( $path );
		if( FALSE === $pem )
			throw new RuntimeException( 'Cannot read private key file: '.$path );

		$this->setPrivateKeyFromString( trim( $pem ) );
	}

	/**
	 *	@param		string[]	$headers
	 *	@return		string[]
	 *	@throws		InvalidArgumentException	if a forbidden header is given
	 */
	protected static function normalizeHeaderNames( array $headers ): array
	{
		$headers	= array_values( array_unique( array_map(
			static fn( string $name ): string => strtolower( trim( $name ) ),
			$headers
		) ) );
		foreach( $headers as $name ){
			if( in_array( $name, self::FORBIDDEN_HEADERS, TRUE ) )
				throw new InvalidArgumentException( 'Header "'.$name.'" must not be signed' );
			if( '' === $name || 1 === preg_match( '/[^\x21-\x39\x3B-\x7E]/', $name ) )
				throw new InvalidArgumentException( 'Invalid header name "'.$name.'"' );
		}
		return $headers;
	}

	/**
	 *	Renders the DKIM-Signature header field, ending with empty b= tag, folded at tag and header name boundaries.
	 *	@access		protected
	 *	@param		array<string,string>	$tags			Tags before h=, bh= last
	 *	@param		string[]				$signedHeaders	Names for h= tag
	 *	@param		string					$eol			Line ending
	 *	@return		string
	 */
	protected function renderField( array $tags, array $signedHeaders, string $eol ): string
	{
		$bodyHash	= $tags['bh'];
		unset( $tags['bh'] );

		$atoms	= [];																//  pairs of glue and atom, fold possible before atom
		foreach( $tags as $key => $value )
			$atoms[]	= [' ', $key.'='.$value.';'];
		$last	= count( $signedHeaders ) - 1;
		foreach( $signedHeaders as $nr => $name )
			$atoms[]	= 0 === $nr ? [' ', 'h='.$name.( $nr === $last ? ';' : '' )] : ['', ':'.$name.( $nr === $last ? ';' : '' )];
		$atoms[]	= [' ', 'bh='.$bodyHash.';'];
		$atoms[]	= [' ', 'b='];

		$lines	= [];
		$line	= 'DKIM-Signature:';
		foreach( $atoms as [$glue, $atom] ){
			if( strlen( $line ) + strlen( $glue ) + strlen( $atom ) > self::LINE_LENGTH ){
				$lines[]	= $line;
				$line		= ' '.$atom;
			}
			else
				$line		.= $glue.$atom;
		}
		$lines[]	= $line;
		return implode( $eol, $lines );
	}

	/**
	 *	Set the private key from a PEM string.
	 *	@access		protected
	 *	@param		string		$pem		PEM-encoded private RSA key
	 *	@throws		RuntimeException	if key cannot be parsed, is no RSA key or too short
	 */
	protected function setPrivateKeyFromString( string $pem ): void
	{
		$key	= @openssl_pkey_get_private( $pem );
		if( FALSE === $key )
			throw new RuntimeException( 'Cannot parse private key: invalid PEM format' );
		$details	= openssl_pkey_get_details( $key );
		if( FALSE === $details || OPENSSL_KEYTYPE_RSA !== $details['type'] )
			throw new RuntimeException( 'Private key must be an RSA key' );
		if( $details['bits'] < self::MIN_KEY_BITS )
			throw new RuntimeException( 'RSA key must have at least '.self::MIN_KEY_BITS.' bits, 2048 recommended' );
		$this->privateKey	= $key;
	}

	/**
	 *	Sign a string using the private key.
	 *	@access		protected
	 *	@param		string		$data		Data to sign
	 *	@return		string					Raw signature bytes
	 *	@throws		RuntimeException	if signing fails
	 */
	protected function signString( string $data ): string
	{
		if( NULL === $this->privateKey )
			throw new RuntimeException( 'No private key loaded for DKIM signing' );

		$signature	= '';
		if( !openssl_sign( $data, $signature, $this->privateKey, OPENSSL_ALGO_SHA256 ) )
			throw new RuntimeException( 'DKIM signing failed: openssl_sign error' );
		return $signature;
	}
}
