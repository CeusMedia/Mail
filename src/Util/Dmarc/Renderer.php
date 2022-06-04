<?php
declare(strict_types=1);

/**
 *	Renderer for DMARC records.
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
use CeusMedia\Mail\Conduct\RegularStringHandling;
use CeusMedia\Mail\Deprecation;

use function count;
use function join;
use function strlen;

/**
 *	Renderer for DMARC records.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util_Dmarc
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Renderer
{
	use RegularStringHandling;

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

	public function render( Record $record ): string
	{
		$facts	= [
			'v'		=> 'DMARC'.$record->version,
			'p'		=> $record->policy,
		];
		if( self::strHasContent( $record->policySubdomains ) )
			$facts['sp']	= $record->policySubdomains;
		$facts['adkim']	= $record->alignmentDkim;
		$facts['aspf']	= $record->alignmentSpf;
		$facts['pct']	= $record->percent;
		if( 0 < count( $record->reportAggregate ) ){
			$list	= [];
			foreach( $record->reportAggregate as $uri ){
				if( $uri instanceof Address )
					$uri	= 'mailto:'.$uri->get();
				$list[]	= $uri;
			}
			$facts['rua']	= join( ', ', $list );
		}
		if( 0 < count( $record->reportForensic ) ){
			$list	= [];
			foreach( $record->reportForensic as $uri ){
				if( $uri instanceof Address )
					$uri	= 'mailto:'.$uri->get();
				$list[]	= $uri;
			}
			$facts['ruf']	= join( ', ', $list );
		}
		if( $record->interval != 86400 )
			$facts['ri']	= $record->interval;
		if( $record->failureOption != '0' )
			$facts['fo']	= $record->failureOption;

		$list	= [];
		foreach( $facts as $key => $value )
			$list[]	= $key.'='.(string) $value;
		return join( '; ', $list );
	}
}
