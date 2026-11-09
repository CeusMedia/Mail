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

use function base64_encode;
use function explode;
use function implode;
use function openssl_pkey_get_private;
use function openssl_sign;
use function strlen;
use function strtolower;
use function substr;
use function trim;

/**
 *	DKIM message signer (RFC 6376).
 *	Generates a DKIM-Signature header and appends it to the message before sending.
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
	/** @var mixed  $privateKey  OpenSSL private key (resource or OpenSSLAsymmetricKey) */
	protected $privateKey	= NULL;

	/** @var string  $domain       DKIM signing domain */
	protected string $domain;

	/** @var string  $selector     DKIM key selector */
	protected string $selector;

	/** @var string  $algorithm    DKIM algorithm (default: rsa-sha256) */
	protected string $algorithm	= 'rsa-sha256';

	/** @var string[]  $headers    Headers to sign (default: from, date, subject) */
	protected array $headers	= [ 'from', 'date', 'subject' ];

	/** @var Canonicalizer  $canonicalizer  */
	protected Canonicalizer $canonicalizer;

	/** @var int  $signatureLength  Max length of a signature line (for folding) */
	protected int $signatureLength	= 68;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$privateKeyPath	Path to PEM-encoded private key file
	 *	@param		string		$domain			DKIM signing domain
	 *	@param		string		$selector			DKIM key selector (DNS record name)
	 *	@param		string		$headerMode		Canonicalization mode for headers (simple|relaxed)
	 *	@param		string		$bodyMode			Canonicalization mode for body (simple|relaxed)
	 *	@throws		InvalidArgumentException	if private key file is not readable
	 *	@throws		RuntimeException			if private key cannot be parsed
	 */
	public function __construct(
		string $privateKeyPath,
		string $domain,
		string $selector,
		string $headerMode = Canonicalizer::RELAXED,
		string $bodyMode	= Canonicalizer::RELAXED
	)
	{
		$this->domain		= $domain;
		$this->selector	= $selector;
		$this->canonicalizer	= new Canonicalizer( $headerMode, $bodyMode );

		$this->loadPrivateKey( $privateKeyPath );
	}

	/**
	 *	Constructor with raw private key string.
	 *	@access		public
	 *	@static
	 *	@param		string		$privateKey		PEM-encoded private key string
	 *	@param		string		$domain			DKIM signing domain
	 *	@param		string		$selector			DKIM key selector
	 *	@param		string		$headerMode		Canonicalization mode for headers
	 *	@param		string		$bodyMode			Canonicalization mode for body
	 *	@return		self
	 *	@throws		InvalidArgumentException	if private key is invalid
	 *	@throws		RuntimeException			if private key cannot be parsed
	 */
	public static function createFromKey(
		string $privateKey,
		string $domain,
		string $selector,
		string $headerMode = Canonicalizer::RELAXED,
		string $bodyMode	= Canonicalizer::RELAXED
	): self
	{
		$dkim		= new self( '', $domain, $selector, $headerMode, $bodyMode );
		$dkim->setPrivateKeyFromString( $privateKey );
		return $dkim;
	}

	/**
	 *	Sets the list of headers to sign.
	 *	@access		public
	 *	@param		string[]	$headers		Header names (case-insensitive)
	 *	@return		self
	 */
	public function setHeaders( array $headers ): self
	{
		$this->headers	= array_map( 'strtolower', $headers );
		return $this;
	}

	/**
	 *	Sets the DKIM algorithm.
	 *	@access		public
	 *	@param		string		$algorithm		Algorithm identifier (default: rsa-sha256)
	 *	@return		self
	 */
	public function setAlgorithm( string $algorithm ): self
	{
		$this->algorithm	= strtolower( $algorithm );
		return $this;
	}

	/**
	 *	Sign a rendered message and return the message with the DKIM-Signature header appended.
	 *	The DKIM-Signature header is inserted after the existing headers, before the body.
	 *	@access		public
	 *	@param		string		$message		Full rendered message (headers + body, separated by blank line)
	 *	@return		string					Message with DKIM-Signature header added
	 */
	public function sign( string $message ): string
	{
		//  Split message into headers and body
		$parts		= explode( "\n\n", $message, 2 );
		$headers	= $parts[0];
		$body		= $parts[1] ?? '';

		//  Normalize header line endings
		$headers	= str_replace( "\r\n", "\n", $headers );

		//  Compute body hash
		$canonicalBody	= $this->canonicalizer->canonicalizeBody( $body );
		$bodyHash		= hash( 'sha256', $canonicalBody, TRUE );
		$bodyHashB64	= base64_encode( $bodyHash );

		//  Build DKIM-Signature header parameters
		$params		= [];
		$params['v']		= '1';
		$params['a']		= $this->algorithm;
		$params['c']		= $this->canonicalizer->getMode();
		$params['d']		= $this->domain;
		$params['h']		= implode( ':', $this->headers );
		$params['i']		= '@'.$this->domain;
		$params['s']		= $this->selector;
		$params['t']		= (string) time();
		$params['bh']		= $bodyHashB64;

		//  Render DKIM-Signature header value (without the signature itself)
		$sigParams	= $this->renderParams( $params, FALSE );
		$dkimHeader	= "DKIM-Signature: ".$sigParams;

		//  Add DKIM-Signature to headers for signing
		$headersToSign	= $headers."\n".$dkimHeader."\n";

		//  Canonicalize headers for signing
		$canonicalHeaders	= $this->canonicalizer->canonicalizeHeaderList(
			$headersToSign,
			array_merge( $this->headers, [ 'dkim-signature' ] )
		);

		//  Sign the canonicalized headers
		$signature	= $this->signString( $canonicalHeaders );
		$sigB64		= base64_encode( $signature );

		//  Build final DKIM-Signature header with signature
		$params['b']	= $sigB64;
		$finalSigParams	= $this->renderParams( $params, TRUE );
		$finalDkimHeader	= "DKIM-Signature: ".$finalSigParams;

		//  Fold the DKIM-Signature header if too long
		$foldedHeader	= $this->foldHeader( $finalDkimHeader );

		//  Insert DKIM-Signature after existing headers
		return $headers."\n".$foldedHeader."\n\n".$body;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Load a private key from a PEM file.
	 *	@access		protected
	 *	@param		string		$path		Path to PEM file
	 *	@throws		InvalidArgumentException	if file not readable
	 *	@throws		RuntimeException			if key cannot be parsed
	 */
	protected function loadPrivateKey( string $path ): void
	{
		if( strlen( $path ) === 0 ){
			//  Empty path — key will be set later via setPrivateKeyFromString
			return;
		}

		if( !is_readable( $path ) ){
			throw new InvalidArgumentException( 'Private key file "'.$path.'" is not readable' );
		}

		$pem	= file_get_contents( $path );
		if( FALSE === $pem ){
			throw new RuntimeException( 'Cannot read private key file: '.$path );
		}

		$this->setPrivateKeyFromString( trim( $pem ) );
	}

	/**
	 *	Set the private key from a PEM string.
	 *	@access		protected
	 *	@param		string		$pem		PEM-encoded private key
	 *	@throws		RuntimeException	if key cannot be parsed
	 */
	protected function setPrivateKeyFromString( string $pem ): void
	{
		$key	= @openssl_pkey_get_private( $pem );
		if( FALSE === $key ){
			throw new RuntimeException( 'Cannot parse private key: invalid PEM format' );
		}
		$this->privateKey	= $key;
	}

	/**
	 *	Sign a string using the private key.
	 *	@access		protected
	 *	@param		string		$data		Data to sign
	 *	@return		string				Raw signature bytes
	 *	@throws		RuntimeException	if signing fails
	 */
	protected function signString( string $data ): string
	{
		if( NULL === $this->privateKey ){
			throw new RuntimeException( 'No private key loaded for DKIM signing' );
		}

		$signature	= '';
		$result		= openssl_sign( $data, $signature, $this->privateKey, 'sha256' );
		if( FALSE === $result ){
			throw new RuntimeException( 'DKIM signing failed: openssl_sign error' );
		}

		return $signature;
	}

	/**
	 *	Render DKIM-Signature parameters as a semicolon-separated string.
	 *	@access		protected
	 *	@param		array<string,string>	$params		Parameter key-value pairs
	 *	@param		boolean					$fold		Flag: fold long lines
	 *	@return		string
	 */
	protected function renderParams( array $params, bool $fold = TRUE ): string
	{
		$parts	= [];
		foreach( $params as $key => $value ){
			$parts[]	= $key.'='.$value;
		}
		return implode( '; ', $parts );
	}

	/**
	 *	Fold a long header line at 78 characters (RFC 5322).
	 *	@access		protected
	 *	@param		string		$header		Header line (name: value)
	 *	@return		string					Folded header
	 */
	protected function foldHeader( string $header ): string
	{
		$maxLength	= 78;
		if( strlen( $header ) <= $maxLength )
			return $header;

		$nameEnd	= strpos( $header, ':' );
		if( FALSE === $nameEnd )
			return $header;

		$name	= substr( $header, 0, $nameEnd + 1 );
		$value	= substr( $header, $nameEnd + 1 );
		$value	= trim( $value );

		$lines	= [];
		$first	= TRUE;
		while( strlen( $value ) > 0 ){
			$lineLen	= $first ? ( $maxLength - strlen( $name ) ) : ( $maxLength - 1 );
			if( strlen( $value ) <= $lineLen ){
				$lines[]	= ( $first ? '' : " " ).$value;
				$value	= '';
			}
			else{
				$lines[]	= ( $first ? '' : " " ).substr( $value, 0, $lineLen );
				$value	= substr( $value, $lineLen );
			}
			$first	= FALSE;
		}

		return $name.implode( "\r\n", $lines );
	}
}

