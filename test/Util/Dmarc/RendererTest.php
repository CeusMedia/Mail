<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';

use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Util\Dmarc\Record;
use \CeusMedia\Mail\Util\Dmarc\Renderer;

/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class RendererTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct(){
	}

	public function testRender(){
		$record	= new Record();
		$record->reportAggregate	= array( new Address( 'postmaster1@ceusmedia.de' ) );
		$record->reportForensic		= array( new Address( 'postmaster2@ceusmedia.de' ) );
		$record->policy				= Record::POLICY_QUARANTINE;
		$record->policySubdomains	= Record::POLICY_REJECT;
		$record->interval			= 3600;
		$record->alignmentSpf		= Record::ALIGNMENT_STRICT;
		$record->alignmentDkim		= Record::ALIGNMENT_STRICT;
		$record->percent			= 90;
		$record->failureOption		= Record::REPORT_IF_ANY_FAILED;

		$rendered	= Renderer::render( $record );
		$dmarc		= "v=DMARC1; p=quarantine; sp=reject; pct=90; rua=mailto:postmaster1@ceusmedia.de; ruf=mailto:postmaster2@ceusmedia.de; adkim=s; aspf=s; fo=1; ri=3600";

		$rendered	= preg_split( '/; /', $rendered );
		$dmarc		= preg_split( '/; /', $dmarc );
 		sort( $rendered );
 		sort( $dmarc );
		$this->assertEquals( $dmarc, $rendered );

	}
}
