<?php
/**
 *	Parser for DMARC records.
 *
 *	Copyright (c) 2017-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Util\Dmarc;

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Util\Dmarc\Record;

/**
 *	Parser for DMARC records.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util_Dmarc
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Parser
{
	public static function create()
	{
		return new static();
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
		foreach( $pairs as $pair ){
			if( preg_match( '/=/', $pair ) ){
				$pair	= preg_split( '/\s*=\s*/', $pair, 2 );
				switch( $pair[0] ){
					case 'v':
						$record->version	= preg_replace( '/^DMARC/', '', $pair[1] );
						break;
					case 'p':
						$values	= array( 'none', 'quarantine', 'reject' );
						if( in_array( $pair[1], $values ) )
							$record->policy				= $pair[1];
						break;
					case 'sp':
						$values	= array( 'none', 'quarantine', 'reject' );
						if( in_array( $pair[1], $values ) )
							$record->policySubdomains	= $pair[1];
						break;
					case 'adkim':
						if( in_array( $pair[1], array( 'r', 's' ) ) )
							$record->alignmentDkim		= $pair[1];
						break;
					case 'aspf':
						if( in_array( $pair[1], array( 'r', 's' ) ) )
							$record->alignmentSpf		= $pair[1];
						break;
					case 'pct':
						$record->percent				= min( 100, max( 0, intval( $pair[1] ) ) );
						break;
					case 'rua':
						foreach( preg_split( '/\s*,\s*/', $pair[1] ) as $part ){
							if( preg_match( '/^mailto:/', $part ) )
								$part	= new Address( preg_replace( '/^mailto:/', '', $part ) );
							$record->reportAggregate[]	= $part;
						}
						break;
					case 'ruf':
						foreach( preg_split( '/\s*,\s*/', $pair[1] ) as $part ){
							if( preg_match( '/^mailto:/', $part ) )
								$part	= new Address( preg_replace( '/^mailto:/', '', $part ) );
							$record->reportForensic[]	= $part;
						}
						break;
					case 'ri':
						$record->interval				= abs( intval( $pair[1] ) );
						break;
					case 'fo':
						if( in_array( $pair[1], array( '0', '1', 'd', 's' ) ) )
							$record->failureOption		= $pair[1];
						break;
				}
			}
		}
		return $record;
	}
}
