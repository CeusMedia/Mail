<?php
/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 */

namespace CeusMedia\MailTest\Unit\Message\Header;

use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message\Header\Received;
use CeusMedia\MailTest\TestCase;

use DateTime;
use DateTimeImmutable;

/**
 *	Unit test for mail message header parser.
 *	@category			Test
 *	@package			CeusMedia_MailTest_Unit_Message_Header
 *	@author				Christian Würker <christian.wuerker@ceusmedia.de>
 *  @coversDefaultClass \CeusMedia\Mail\Message\Header\Received
 */
class ReceivedTest extends TestCase
{
	/**
	 *	@covers		::parse
	 *	@covers		::maskWordGroups
	 *	@covers		::storeRetrievedData
	 *	@covers		::toArray
	 */
	public function testParse()
	{
		date_default_timezone_set('Europe/Berlin');

		$input	= 'from [IPv6:2a02:810a:113f:eb04:25f1:e53b:6f7f:a6c2] (unknown [IPv6:2a02:810a:113f:eb04:25f1:e53b:6f7f:a6c2]) by viratron.itflow.de (Postfix) with ESMTPSA id 2CF231662D3 for <test@gruppenpost.de>; Thu, 30 Nov 2017 23:33:01 +0100 (CET)';
		$object	= Received::parse( $input );
		$array	= [
			'from'	=> '[IPv6:2a02:810a:113f:eb04:25f1:e53b:6f7f:a6c2] (unknown [IPv6:2a02:810a:113f:eb04:25f1:e53b:6f7f:a6c2])',
			'by'	=> 'viratron.itflow.de (Postfix)',
			'with'	=> 'ESMTPSA',
			'id'	=> '2CF231662D3',
			'via'	=> NULL,
			'for'	=> new Address( 'test@gruppenpost.de' ),
			'date'	=> new DateTimeImmutable( 'Thu, 30 Nov 2017 23:33:01 +0100' ),
		];
		self::assertEquals( $array, $object->toArray() );
		self::assertEquals( $array['from'], $object->getFrom() );
		self::assertEquals( $array['by'], $object->getBy() );
		self::assertEquals( $array['with'], $object->getWith() );
		self::assertEquals( $array['id'], $object->getId() );
		self::assertTrue( is_object( $object->getFor() ) );
		self::assertTrue( $object->getFor() instanceof Address );
		self::assertEquals( $array['for']->get(), $object->getFor()->get() );
		self::assertTrue( is_object( $object->getDate() ) );
		self::assertEquals( 'DateTimeImmutable', get_class( $object->getDate() ) );
		self::assertEquals( $array['date']->format( 'r' ), $object->getDate()->format( 'r' ) );

		$input	= 'from b231-214.smtp-out.eu-west-1.amazonses.com (b231-214.smtp-out.eu-west-1.amazonses.com. [69.169.231.214]) by mx.google.com with ESMTPS id o4si13584888eje.262.2021.06.22.05.25.39 for <christian.wuerker@gmail.com> (version=TLS1_2 cipher=ECDHE-ECDSA-AES128-SHA bits=128/128); Tue, 22 Jun 2021 05:25:39 -0700 (PDT)';
		$object	= Received::parse( $input );
		$array	= [
			'from'	=> 'b231-214.smtp-out.eu-west-1.amazonses.com (b231-214.smtp-out.eu-west-1.amazonses.com. [69.169.231.214])',
			'by'	=> 'mx.google.com',
			'with'	=> 'ESMTPS',
			'id'	=> 'o4si13584888eje.262.2021.06.22.05.25.39',
			'via'	=> NULL,
			'for'	=> new Address( 'christian.wuerker@gmail.com' ),
			'date'	=> new DateTimeImmutable( 'Tue, 22 Jun 2021 05:25:39 -0700' ),
		];
		self::assertEquals( $array, $object->toArray() );
		self::assertEquals( $array['from'], $object->getFrom() );
		self::assertEquals( $array['by'], $object->getBy() );
		self::assertEquals( $array['with'], $object->getWith() );
		self::assertEquals( $array['id'], $object->getId() );
		self::assertTrue( is_object( $object->getFor() ) );
		self::assertTrue( $object->getFor() instanceof Address );
		self::assertEquals( $array['for']->get(), $object->getFor()->get() );
		self::assertTrue( is_object( $object->getDate() ) );
		self::assertEquals( 'DateTimeImmutable', get_class( $object->getDate() ) );
		self::assertEquals( $array['date']->format( 'r' ), $object->getDate()->format( 'r' ) );
	}

	/**
	 *	@covers		::getFrom
	 *	@covers		::setFrom
	 */
	public function testGetSetFrom()
	{
		$object	= new Received();
		$result	= $object->setFrom( 'value _#1 ' );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertEquals( 'value _#1 ', $object->getFrom() );
	}

	/**
	 *	@covers		::getBy
	 *	@covers		::setBy
	 */
	public function testGetSetBy()
	{
		$object	= new Received();
		$result	= $object->setBy( 'value _#1 ' );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertEquals( 'value _#1 ', $object->getBy() );
	}

	/**
	 *	@covers		::getWith
	 *	@covers		::setWith
	 */
	public function testGetSetWith()
	{
		$object	= new Received();
		$result	= $object->setWith( 'value _#1 ' );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertEquals( 'value _#1 ', $object->getWith() );
	}

	/**
	 *	@covers		::getId
	 *	@covers		::setId
	 */
	public function testGetSetId()
	{
		$object	= new Received();
		$result	= $object->setId( 'value _#1 ' );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertEquals( 'value _#1 ', $object->getId() );
	}

	/**
	 *	@covers		::getDate
	 *	@covers		::setDate
	 */
	public function testGetSetDate()
	{
		$object	= new Received();
		$result	= $object->setDate( new DateTimeImmutable( 'Tue, 22 Jun 2021 05:25:39 -0700 (PDT)' ) );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertTrue( is_object( $object->getDate() ) );
		self::assertEquals( 'DateTimeImmutable', get_class( $object->getDate() ) );
		self::assertEquals( 'Tue, 22 Jun 2021 05:25:39 -0700', $object->getDate()->format( 'r' ) );
	}

	/**
	 *	@covers		::getVia
	 *	@covers		::setVia
	 */
	public function testGetSetVia()
	{
		$object	= new Received();
		$result	= $object->setVia( 'value _#1 ' );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertEquals( 'value _#1 ', $object->getVia() );
	}

	/**
	 *	@covers		::getFor
	 *	@covers		::setFor
	 */
	public function testGetSetFor()
	{
		$object	= new Received();
		$result	= $object->setFor( new Address( 'hans.testmann@test.com' ) );
		self::assertTrue( is_object( $result ) );
		self::assertEquals( $object, $result );
		self::assertTrue( is_object( $object->getFor() ) );
		self::assertTrue( $object->getFor() instanceof Address );
		self::assertEquals( 'hans.testmann@test.com', $object->getFor()->get() );
	}
}
