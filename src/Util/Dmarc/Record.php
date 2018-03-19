<?php
/**
 *	Model for DMARC records.
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
 *	@package		CeusMedia_Mail_Util_Dmarc
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2018 Christian Würker
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
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
class Record{
	const POLICY_UNKNOWN		= NULL;
	const POLICY_NONE			= 'none';
	const POLICY_QUARANTINE		= 'quarantine';
	const POLICY_REJECT			= 'reject';

	const ALIGNMENT_RELAXED		= 'r';
	const ALIGNMENT_STRICT		= 's';

	const REPORT_IF_ALL_FAILED	= '0';
	const REPORT_IF_ANY_FAILED	= '1';
	const REPORT_IF_DKIM_FAILED	= 'd';
	const REPORT_IF_SPF_FAILED	= 's';

	public $version				= '1';
	public $policy				= SELF::POLICY_NONE;
	public $policySubdomains	= SELF::POLICY_UNKNOWN;
	public $interval			= 86400;
	public $reportAggregate		= array();
	public $reportForensic		= array();
	public $alignmentDkim		= SELF::ALIGNMENT_RELAXED;
	public $alignmentSpf		= SELF::ALIGNMENT_RELAXED;
	public $percent				= 100;
	public $failureOption		= SELF::REPORT_IF_ALL_FAILED;
}
