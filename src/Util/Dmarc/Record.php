<?php
declare(strict_types=1);

/**
 *	Model for DMARC records.
 *
 *	Copyright (c) 2017-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Util\Dmarc;

/**
 *	Model for DMARC records.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Util_Dmarc
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Record
{
	const POLICY_UNKNOWN		= '';
	const POLICY_NONE			= 'none';
	const POLICY_QUARANTINE		= 'quarantine';
	const POLICY_REJECT			= 'reject';

	const ALIGNMENT_RELAXED		= 'r';
	const ALIGNMENT_STRICT		= 's';

	const REPORT_IF_ALL_FAILED	= '0';
	const REPORT_IF_ANY_FAILED	= '1';
	const REPORT_IF_DKIM_FAILED	= 'd';
	const REPORT_IF_SPF_FAILED	= 's';

	/**	@var	string			$version */
	public $version				= '1';

	/**	@var	string			$policy */
	public $policy				= self::POLICY_NONE;

	/**	@var	string			$policySubdomains */
	public $policySubdomains	= self::POLICY_UNKNOWN;

	/**	@var	integer			$interval */
	public $interval			= 86400;

	/**	@var	array			$reportAggregate */
	public $reportAggregate		= [];

	/**	@var	array			$reportForensic */
	public $reportForensic		= [];

	/**	@var	string			$alignmentDkim */
	public $alignmentDkim		= self::ALIGNMENT_RELAXED;

	/**	@var	string			$alignmentSpf */
	public $alignmentSpf		= self::ALIGNMENT_RELAXED;

	/**	@var	integer			$percent */
	public $percent				= 100;

	/**	@var	string			$failureOption */
	public $failureOption		= self::REPORT_IF_ALL_FAILED;
}
