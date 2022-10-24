<?php
declare(strict_types=1);

/**
*	...
 *
 *	Copyright (c) 2021-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2021-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail;

use CeusMedia\Common\Deprecation as CommonDeprecation;

use RuntimeException;

use function dirname;
use function file_exists;
use function parse_ini_file;
use function phpversion;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Deprecation extends CommonDeprecation
{
	/**
	 *	Event to handle self detection on end of static construction.
	 *	Will detect library version.
	 *	Will set error version to current library version by default.
	 *	Will not set an exception version.
	 *	@access		protected
	 *	@return		void
	 */
	protected function onInit(): void
	{
		parent::__construct();
		$iniFilePath		= dirname( __DIR__ ).'/Mail.ini';
		if( !file_exists( $iniFilePath ) )
			$iniFilePath		.= '.dist';
		$iniFileData		= parse_ini_file( $iniFilePath, TRUE );
		if( FALSE === $iniFileData )
			throw new RuntimeException( 'Loading library configuration failed' );
		$this->version		= $iniFileData['library']['version'];
		$this->errorVersion	= $this->version;
	}
}
