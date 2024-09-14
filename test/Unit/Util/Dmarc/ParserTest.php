<?php
/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Util_Dmarc
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Util\Dmarc;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Util\Dmarc\Record;
use CeusMedia\Mail\Util\Dmarc\Parser;
use CeusMedia\MailTest\TestCase;

/**
 *	Unit test for mail address.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Util_Dmarc
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Util\Dmarc\Parser
 */
class ParserTest extends TestCase
{
	/**
	 *	@covers		::create
	 */
	public function testCreate()
	{
		$instance	= Parser::getInstance();
		$this->assertTrue( is_object( $instance ) );
		$this->assertTrue( $instance instanceof Parser );
		$this->assertTrue( get_class( $instance ) === 'CeusMedia\Mail\Util\Dmarc\Parser' );
	}

	/**
	 *	@covers		::parse
	 */
	public function testParse()
	{
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

		$dmarc	= "v=DMARC1;p=quarantine;sp=reject;pct=90;rua=mailto:postmaster1@ceusmedia.de;ruf=mailto:postmaster2@ceusmedia.de;adkim=s;aspf=s;fo=1;ri=3600";
		$parsed	= Parser::getInstance()->parse( $dmarc );
		$this->assertEquals( $record, $parsed );
	}
}
