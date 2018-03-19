<?php
/**
 *	Unit test for mail address.
 *	@category		Test
 *	@package		CeusMedia_Mail
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
require_once dirname( dirname( __DIR__ ) ).'/bootstrap.php';
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
		$record	= new \CeusMedia\Mail\Util\Dmarc\Record();
		$record->reportAggregate	= array( new \CeusMedia\Mail\Address( 'postmaster1@ceusmedia.de' ) );
		$record->reportForensic		= array( new \CeusMedia\Mail\Address( 'postmaster2@ceusmedia.de' ) );
		$record->policy				= \CeusMedia\Mail\Util\Dmarc\Record::POLICY_QUARANTINE;
		$record->policySubdomains	= \CeusMedia\Mail\Util\Dmarc\Record::POLICY_REJECT;
		$record->interval			= 3600;
		$record->alignmentSpf		= \CeusMedia\Mail\Util\Dmarc\Record::ALIGNMENT_STRICT;
		$record->alignmentDkim		= \CeusMedia\Mail\Util\Dmarc\Record::ALIGNMENT_STRICT;
		$record->percent			= 90;
		$record->failureOption		= \CeusMedia\Mail\Util\Dmarc\Record::REPORT_IF_ANY_FAILED;

		$rendered	= \CeusMedia\Mail\Util\Dmarc\Renderer::render( $record );
		$dmarc		= "v=DMARC1; p=quarantine; sp=reject; pct=90; rua=mailto:postmaster1@ceusmedia.de; ruf=mailto:postmaster2@ceusmedia.de; adkim=s; aspf=s; fo=1; ri=3600";

		$rendered	= preg_split( '/; /', $rendered );
		$dmarc		= preg_split( '/; /', $dmarc );
 		sort( $rendered );
 		sort( $dmarc );
		$this->assertEquals( $dmarc, $rendered );

	}
}
