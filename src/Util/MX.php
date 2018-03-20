<?php
/**
 *	Resolver for DNS MX records related to hostname or mail address.
 *
 *	Copyright (c) 2017-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Util;
/**
 *	Resolver for DNS MX records related to hostname or mail address.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class MX{

	protected $cache;

	protected $useCache		= FALSE;

	public function __construct(){}

	public function fromAddress( $address, $useCache = TRUE, $strict = TRUE ){
		if( is_string( $address ) )
			$address	= new \CeusMedia\Mail\Address( $address );
		$hostname	= $address->getDomain();
		return $this->fromHostname( $address->getDomain(), $useCache, $strict );
	}

	public function fromHostname( $hostname, $useCache = TRUE, $strict = TRUE ){
		$useCache	= $useCache && $this->useCache;
		if( $useCache && $this->cache->has( 'mx:'.$hostname ) )
			return $this->cache->get( 'mx:'.$hostname );
		$servers	= array();
		getmxrr( $hostname, $mxRecords, $mxWeights );
		if( !$mxRecords && $strict )
			throw new \RuntimeException( 'No MX records found for host: '.$hostname );
		foreach( $mxRecords as $nr => $server )
			$servers[$mxWeights[$nr]]	= $server;
		ksort( $servers );
		if( $useCache )
			$this->cache->set( 'mx:'.$hostname, $servers );
		return $servers;
	}

	public function setCache( $cache ){
		$this->useCache		= (bool) $cache;
		$this->cache		= $cache;
	}
}