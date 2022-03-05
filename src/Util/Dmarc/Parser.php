<?php
declare(strict_types=1);

/**
 *	Parser for DMARC records.
 *
 *	Copyright (c) 2017-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Util_Dmarc
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Util\Dmarc;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Deprecation;
use CeusMedia\Mail\Util\Dmarc\Record;

use RuntimeException;

use function abs;
use function in_array;
use function intval;
use function max;
use function min;
use function preg_match;
use function preg_replace;
use function preg_split;
use function rtrim;
use function trim;

/**
 *	Parser for DMARC records.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util_Dmarc
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Parser
{
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
	 *	@access		public
	 *	@param		string		$content		...
	 *	@return		Record
	 */
	public function parse( string $content ): Record
	{
		$record		= new Record();
		$content	= rtrim( trim( $content ), ';' );
		$pairs		= preg_split( '/\s*;\s*/', $content );
		if( FALSE !== $pairs ){
			foreach( $pairs as $pair ){
				if( FALSE === preg_match( '/=/', $pair ) )
					continue;
				$pair	= preg_split( '/\s*=\s*/', $pair, 2 );
				if( FALSE === $pair )
					continue;
				switch( $pair[0] ){
					case 'v':
						$version	= preg_replace( '/^DMARC/', '', $pair[1] );
						if( NULL === $version )
							throw new RuntimeException( 'Parsing version failed' );
						$record->version	= $version;
						break;
					case 'p':
						$values	= [ 'none', 'quarantine', 'reject' ];
						if( in_array( $pair[1], $values, TRUE ) )
							$record->policy				= $pair[1];
						break;
					case 'sp':
						$values	= [ 'none', 'quarantine', 'reject' ];
						if( in_array( $pair[1], $values, TRUE ) )
							$record->policySubdomains	= $pair[1];
						break;
					case 'adkim':
						if( in_array( $pair[1], [ 'r', 's' ], TRUE ) )
							$record->alignmentDkim		= $pair[1];
						break;
					case 'aspf':
						if( in_array( $pair[1], [ 'r', 's' ], TRUE ) )
							$record->alignmentSpf		= $pair[1];
						break;
					case 'pct':
						$record->percent	= min( 100, max( 0, intval( $pair[1] ) ) );
						break;
					case 'rua':
						$parts = preg_split( '/\s*,\s*/', $pair[1] );
						if( FALSE !== $parts ){
							foreach( $parts as $part ){
								if( 1 === preg_match( '/^mailto:/', $part ) )
									$part	= new Address( preg_replace( '/^mailto:/', '', $part ) );
								$record->reportAggregate[]	= $part;
							}
						}
						break;
					case 'ruf':
						$parts	= preg_split( '/\s*,\s*/', $pair[1] );
						if( FALSE !== $parts ){
							foreach( $parts as $part ){
								if( 1 === preg_match( '/^mailto:/', $part ) )
									$part	= new Address( preg_replace( '/^mailto:/', '', $part ) );
								$record->reportForensic[]	= $part;
							}
						}
						break;
					case 'ri':
						$record->interval				= abs( intval( $pair[1] ) );
						break;
					case 'fo':
						if( in_array( $pair[1], [ '0', '1', 'd', 's' ], TRUE ) )
							$record->failureOption		= $pair[1];
						break;
				}
			}
		}
		return $record;
	}
}
