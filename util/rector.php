<?php
declare(strict_types=1);

namespace CeusMedia\Mail;

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\FuncCall\AddPregQuoteDelimiterRector;
use Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector;

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;


return static function (RectorConfig $rectorConfig): void
{
	$rectorConfig->paths([
		__DIR__ . '/../src',
	]);

	// register a single rule
//	$rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
	$rectorConfig->rule(AddPregQuoteDelimiterRector::class);
	$rectorConfig->rule(ArrayKeyExistsTernaryThenValueToCoalescingRector::class);

	// define sets of rules
	$rectorConfig->sets([
		LevelSetList::UP_TO_PHP_74,
//		SetList::DEAD_CODE,
	]);
};
