<?php
declare(strict_types=1);

namespace CeusMedia\Mail;

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void
{
	// get parameters
	$parameters = $containerConfigurator->parameters();
	$parameters->set(Option::PATHS, [
		__DIR__ . '/src'
	]);

	// Path to phpstan with extensions, that PHPStan in Rector uses to determine types
	$parameters->set(Option::PHPSTAN_FOR_RECTOR_PATH, getcwd() . '/phpstan.neon');

	$targetPhpVersion	= PhpVersion::PHP_74;
//	$targetPhpVersion	= PhpVersion::PHP_80;
//	$targetPhpVersion	= PhpVersion::PHP_81;

	$parameters->set(Option::PHP_VERSION_FEATURES, $targetPhpVersion);

	// Define what rule sets will be applied
//    $containerConfigurator->import(LevelSetList::UP_TO_PHP_73);
//	$containerConfigurator->import(SetList::PHP_55);
//	$containerConfigurator->import(SetList::PHP_56);
	$containerConfigurator->import(SetList::PHP_70);
	$containerConfigurator->import(SetList::PHP_71);
	$containerConfigurator->import(SetList::PHP_73);
	$containerConfigurator->import(SetList::PHP_74);
//	$containerConfigurator->import(SetList::PHP_80);
//	$containerConfigurator->import(SetList::PHP_81);


	// get services (needed for register a single rule)
	// $services = $containerConfigurator->services();

	// register a single rule
	// $services->set(TypedPropertyRector::class);
};
