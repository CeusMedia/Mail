<?php
declare(strict_types=1);

/**
 *	Resolver for DNS MX records related to hostname or mail address.
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
 *	@package		CeusMedia_Mail_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Util;

use CeusMedia\Cache\SimpleCacheInterface as CacheInterface;
use CeusMedia\Cache\SimpleCacheFactory as CacheFactory;
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Deprecation;

use RuntimeException;
use Throwable;

use function exec;
use function function_exists;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function preg_match;
use function strlen;
use function trim;

/**
 *	Resolver for DNS MX records related to hostname or mail address.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class MX
{
	/**	@var	CacheInterface	$cache */
	protected $cache;

	/** @var	bool			$useCache */
	protected bool $useCache			= FALSE;

	/**
	 *	Constructor.
	 *	@access		public
	 */
	public function __construct()
	{
	}

	/**
	 *	Static constructor.
	 *	@access			public
	 *	@static
	 *	@return			self
	 *	@deprecated		use getInstance instead
	 *	@todo			to be removed
	 *	@codeCoverageIgnore
	 *	@noinspection	PhpDocMissingThrowsInspection
	 */
	public static function create(): self
	{
		/** @noinspection PhpUnhandledExceptionInspection */
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
	 *	Resolve from mail address.
	 *	@access		public
	 *	@param		Address|string	$address
	 *	@param		boolean			$useCache
	 *	@param		boolean			$strict
	 *	@return		array
	 */
	public function fromAddress( $address, bool $useCache = TRUE, bool $strict = TRUE ): array
	{
		if( is_string( $address ) )
			$address	= new Address( $address );
		return $this->fromHostname( $address->getDomain(), $useCache, $strict );
	}

	/**
	*	Resolve from mail address host name / domain.
	 *	@access		public
	 *	@param		string			$hostname
	 *	@param		boolean			$useCache
	 *	@param		boolean			$strict
	 *	@return		array
	 *	@throws		RuntimeException	if no MX records found in strict mode
	 *	@throws		RuntimeException	if saving found MX records to cache failed
	 */
	public function fromHostname( string $hostname, bool $useCache = TRUE, bool $strict = TRUE ): array
	{
		$useCache	= $useCache && $this->useCache;
		if( $useCache && $this->cache->has( 'mx:'.$hostname ) ){
			/** @var string $json */
			$json	= $this->cache->get( 'mx:'.$hostname );
			$records	= json_decode( $json, TRUE, 512, JSON_THROW_ON_ERROR );
			if( !is_array( $records ) )
				throw new RuntimeException( 'Cache item "mx:'.$hostname.'" is invalid' );
			return $records;
		}
		$servers	= [];
		getmxrr( $hostname, $mxRecords, $mxWeights );
		if( !$mxRecords && $strict )
			throw new RuntimeException( 'No MX records found for host: '.$hostname );
		foreach( $mxRecords as $nr => $server )
			if( array_key_exists( $nr, $mxWeights) )
				$servers[$mxWeights[$nr]]	= $server;
		ksort( $servers );
		if( $useCache && NULL !== $this->cache ){
			try{
				$this->cache->set( 'mx:'.$hostname, json_encode( $servers, JSON_THROW_ON_ERROR ) );
			}
			catch( Throwable $t ){
				throw new RuntimeException( 'Saving found MX records to cache failed', 0, $t );
			}
		}
		return $servers;
	}

	/**
	 *	Sets cache.
	 *	@access		public
	 *	@param		CacheInterface	$cache
	 *	@return		self
	 */
	public function setCache( CacheInterface $cache ): self
	{
		$this->useCache		= (bool) $cache;
		$this->cache		= $cache;
		return $this;
	}
}

if( !function_exists( 'getmxrr' ) )
{
	/**
	 *	support windows platforms
	 *	@codeCoverageIgnore
	 */
	function getmxrr( string $hostname, array &$mxhosts, array &$mxweight ): bool
	{
		$pattern	= "/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.*)$/";
		if( strlen( trim( $hostname ) ) > 0 ){
			$output	= [];
			@exec( "nslookup.exe -type=MX $hostname.", $output );
			$imx	= -1;
			foreach( $output as $line ){
				$imx++;
				$parts	= "";
				if( 1 === preg_match( $pattern, $line, $parts ) ){
					$mxweight[$imx]	= $parts[1];
					$mxhosts[$imx]	= $parts[2];
				}
			}
			return ($imx != -1);
		}
		return FALSE;
	}
}
