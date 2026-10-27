<?php
declare(strict_types=1);

/**
 *	Renderer for mail addresses.
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
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Address;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Header\Encoding as MessageHeaderEncoding;
use CeusMedia\Mail\Util\Str;

use RuntimeException;

use function max;
use function preg_match;
use function strlen;
use function trim;

/**
 *	Renderer for mail addresses.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Address
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@todo			Finish code documentation
 */
class Renderer
{
	/** @var		boolean		$encodeName		Flag: encode name (RFC 2047), for use in headers */
	protected bool $encodeName	= FALSE;

	/** @var		integer		$lineLength		Fold encoded names if greater than 0 (lines with encoded words: 76 at most) */
	protected int $lineLength	= 0;

	/**
	 *	Static constructor.
	 *	@access			public
	 *	@static
	 *	@return			self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 *	@codeCoverageIgnore
	 */
	public static function create(): self
	{
		Deprecation::getInstance()
			->setErrorVersion( '2.5' )
			->setExceptionVersion( '2.6' )
			->message(  'Use method getInstance instead' );
		return new self();
	}

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
	 *	Returns line length for folding encoded names, 0: no folding.
	 *	@access		public
	 *	@return		integer
	 */
	public function getLineLength(): int
	{
		return $this->lineLength;
	}

	/**
	 *	Indicates whether name will be encoded (RFC 2047), for use in headers.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isEncodingName(): bool
	{
		return $this->encodeName;
	}

	/**
	 *	Renders full mail address by given parts.
	 *	Creates patterns 'local-part@domain' and 'name <local-part@domain>'.
	 *	Names are quoted and escaped if needed, e.g. "Max \"Mo\" Muster".
	 *	If encoding names, non-ASCII names are encoded as a whole (RFC 2047), and names of words
	 *	separated by spaces stay unquoted, e.g. Max Muster.
	 *	@access		public
	 *	@param		Address		$address		Address to render
	 *	@param		integer		$offset			Position in line, for encoding names within line length, default: 0
	 *	@return		string		Rendered mail address
	 *	@throws		RuntimeException			If domain is empty
	 *	@throws		RuntimeException			If local part is empty
	 */
	public function render( Address $address, int $offset = 0 ): string
	{
		$domain		= $address->getDomain();
		$localPart	= $address->getLocalPart();
		$name		= trim( $address->getName( FALSE ) ?? '' );
		if( !Str::hasContent( $name ) )
			return $localPart.'@'.$domain;
		if( $this->encodeName && !MessageHeaderEncoding::isPrintableAscii( $name ) )
			//  encode whole name, a partial encoding (like by mb_encode_mimeheader) would leave special characters
			//  outside the encoded words and break an address list, e.g. "Doe, =?UTF-8?B?…?=" for "Doe, Jürgen"
			return $this->renderEncoded( $name, '<'.$localPart.'@'.$domain.'>', $offset );
		if( 1 !== preg_match( $this->encodeName ? '/^[\w ]+$/' : '/^\w+$/', $name ) )
			$name	= MessageHeaderEncoding::quote( $name );						//  quote special characters, e.g. comma or colon
		return $name.' <'.$localPart.'@'.$domain.'>';
	}

	/**
	 *	Sets line length for folding encoded names. Lines with encoded words are 76 characters at most (RFC 2047, 2),
	 *	so any value greater than 0 enables folding at 76 characters.
	 *	@access		public
	 *	@param		integer		$length			Line length, 0: no folding (default)
	 *	@return		self
	 */
	public function setLineLength( int $length ): self
	{
		$this->lineLength	= max( 0, $length );
		return $this;
	}

	/**
	 *	Sets whether to encode name (RFC 2047), needed for use in headers.
	 *	@access		public
	 *	@param		boolean		$encode			Flag: encode name, default: yes
	 *	@return		self
	 */
	public function setEncodeName( bool $encode = TRUE ): self
	{
		$this->encodeName	= $encode;
		return $this;
	}

	/**
	 *	Renders encoded name and address. If folding, encoded words and address are put on new lines
	 *	if line would be too long (RFC 2047, 2).
	 *	@access		protected
	 *	@param		string		$name			Name to encode, containing non-ASCII characters
	 *	@param		string		$angleAddress	Address in angle brackets
	 *	@param		integer		$offset			Position in line
	 *	@return		string
	 */
	protected function renderEncoded( string $name, string $angleAddress, int $offset ): string
	{
		$fold		= 0 !== $this->lineLength;
		$encoded	= MessageHeaderEncoding::getInstance()->encodeWords( $name, 'base64', $fold, $offset );
		$length		= MessageHeaderEncoding::getLastLineLength( $encoded, $offset );
		$tooLong	= $length + 1 + strlen( $angleAddress ) > MessageHeaderEncoding::LINE_LENGTH;
		return $encoded.( $fold && $tooLong ? Message::$delimiter.' ' : ' ' ).$angleAddress;
	}
}
