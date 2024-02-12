<?php
declare(strict_types=1);

/**
 *	Information of this library (and later: repository).
 *
 *	Copyright (c) 2007-2024 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use function dirname;
use function file_exists;
use function parse_ini_file;

/**
 *	Collector container for mails
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Library
{
	/**	@var		string				$identifier		User agent identifier */
	protected static string $identifier;

	/**	@var		string				$version		User agent version */
	protected static string $version;

	/**	@var		bool				$detected		internal flag: detection is done */
	protected static bool $detected		= FALSE;

	/**
	 *	Returns identifier used for mail user agent.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public static function getIdentifier(): ?string
	{
		self::detect();
		return self::$version;
	}

	/**
	 *	Returns mail user agent.
	 *	@access		public
	 *	@return		string
	 */
	public static function getUserAgent(): string
	{
		self::detect();
		return self::$identifier.'/'.self::$version;
	}

	/**
	 *	Returns version used for mail user agent.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public static function getVersion(): ?string
	{
		self::detect();
		return self::$version;
	}

	//  --  PROTECTED  --  //

	/**
	 *	...
	 *	@access		protected
	 *	@return		void
	 */
	protected static function detect(): void
	{
		if( self::$detected )
			return;
		$filePath		= dirname( __DIR__ ).'/Mail.ini';
		if( !file_exists( $filePath ) )
			$filePath	= dirname( __DIR__ ).'/Mail.ini.dist';
		$config		= parse_ini_file( $filePath, TRUE );
		if( FALSE !== $config ){
			self::$identifier	= $config['library']['identifier'] ?? NULL;
			self::$version		= $config['library']['version'] ?? NULL;
			self::$detected		= TRUE;
		}
	}
}
