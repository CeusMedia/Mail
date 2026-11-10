<?php
declare(strict_types=1);

namespace CeusMedia\MailTest\Message\Signer;

use CeusMedia\Mail\Message\Signer\Canonicalizer;
use RuntimeException;

/**
 *	Minimal DKIM verifier for tests (RFC 6376, 6.1), RSA-SHA256 only, public key given instead of DNS lookup.
 *	Selects header fields and removes the b= value on its own, only field and body canonicalization is shared
 *	with the signer - covered by the test vectors of RFC 6376 (3.4.5) and RFC 8463 (Appendix A).
 */
class DkimVerifier
{
	/**
	 *	@param		string		$message		Signed message, CRLF or LF line endings
	 *	@param		string		$publicKey		PEM public key or p= value of DNS record
	 *	@param		int			$index			Position of DKIM-Signature field to verify, from top
	 *	@return		array{bodyHash: bool, signature: bool, tags: array<string,string>}
	 */
	public static function verify( string $message, string $publicKey, int $index = 0 ): array
	{
		$message	= str_replace( "\r\n", "\n", $message );
		$pos		= strpos( $message, "\n\n" );
		$headers	= FALSE === $pos ? $message : substr( $message, 0, $pos );
		$body		= FALSE === $pos ? '' : substr( $message, $pos + 2 );

		$fields		= [];
		foreach( explode( "\n", $headers ) as $line ){
			if( '' !== $line && ( ' ' === $line[0] || "\t" === $line[0] ) )
				$fields[count( $fields ) - 1]	.= "\n".$line;
			else
				$fields[]	= $line;
		}

		$signatures	= array_values( array_filter( $fields, static fn( string $f ): bool => 1 === preg_match( '/^dkim-signature[ \t]*:/i', $f ) ) );
		if( !isset( $signatures[$index] ) )
			throw new RuntimeException( 'No DKIM-Signature at index '.$index );
		$signatureField	= $signatures[$index];

		$tags	= [];
		foreach( explode( ';', substr( $signatureField, (int) strpos( $signatureField, ':' ) + 1 ) ) as $tag ){
			if( '' === trim( $tag ) )
				continue;
			[$key, $value]	= array_map( 'trim', explode( '=', $tag, 2 ) );
			$tags[$key]		= (string) preg_replace( '/[ \t\n]+/', in_array( $key, ['b', 'bh', 'h'], TRUE ) ? '' : ' ', $value );
		}
		if( 'rsa-sha256' !== ( $tags['a'] ?? '' ) )
			throw new RuntimeException( 'Unsupported algorithm' );

		$modes	= explode( '/', $tags['c'] ?? 'simple/simple' );
		$canon	= new Canonicalizer( $modes[0], $modes[1] ?? 'simple' );

		$bodyHash	= base64_encode( hash( 'sha256', $canon->canonicalizeBody( $body ), TRUE ) );

		//  select fields bottom-up per name of h= (RFC 6376, 5.4.2)
		$data	= '';
		$used	= [];
		foreach( explode( ':', strtolower( $tags['h'] ?? '' ) ) as $name ){
			for( $i = count( $fields ) - 1; $i >= 0; $i-- ){
				$fieldName	= strtolower( rtrim( substr( $fields[$i], 0, (int) strpos( $fields[$i], ':' ) ), " \t" ) );
				if( in_array( $i, $used, TRUE ) || $fieldName !== $name )
					continue;
				$used[]	= $i;
				$data	.= $canon->canonicalizeHeaderField( $fields[$i] )."\r\n";
				break;
			}
		}
		//  signature field itself, b= value removed, without trailing CRLF (RFC 6376, 3.7)
		$withoutB	= (string) preg_replace( '/((?:^|;|:)[ \t\n]*b[ \t\n]*=)[^;]*/', '$1', $signatureField );
		$data		.= $canon->canonicalizeHeaderField( $withoutB );

		if( !str_contains( $publicKey, '-----BEGIN' ) )
			$publicKey	= "-----BEGIN PUBLIC KEY-----\n".chunk_split( $publicKey, 64, "\n" )."-----END PUBLIC KEY-----\n";

		return [
			'bodyHash'	=> $bodyHash === ( $tags['bh'] ?? '' ),
			'signature'	=> 1 === openssl_verify( $data, (string) base64_decode( $tags['b'] ?? '', TRUE ), $publicKey, OPENSSL_ALGO_SHA256 ),
			'tags'		=> $tags,
		];
	}
}
