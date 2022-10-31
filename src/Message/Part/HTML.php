<?php
declare(strict_types=1);

/**
 *	HTML Mail Part.
 *
 *	Copyright (c) 2007-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Message\Part;

use CeusMedia\Mail\Message\Part\Text as MessagePartText;

/**
 *	HTML Mail Part.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Message_Part
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class HTML extends MessagePartText
{
	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$content		HTML content
	 *	@param		string		$charset		Character set to set, default: UTF-8
	 *	@param		string		$encoding		Encoding to set, default: base64, values: 7bit,8bit,base64,quoted-printable,binary
	 */
	public function __construct( string $content, string $charset = 'UTF-8', string $encoding = 'base64' )
	{
		parent::__construct( $content, $charset, $encoding );
		$this->type		= static::TYPE_HTML;
		$this->setMimeType( 'text/html' );
	}
}
