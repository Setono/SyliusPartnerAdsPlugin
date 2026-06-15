<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // Scan the PHP service configuration so symbols only referenced there (e.g. nyholm/psr7) are detected.
    ->addPathToScan(__DIR__ . '/config', false)
    ->addPathToExclude(__DIR__ . '/tests')
    // Buzz is an optional PSR-18 client fallback referenced behind interface_exists()/class_exists()
    // guards in the compiler pass and configuration, so it is intentionally a dev dependency.
    ->ignoreErrorsOnPackage('kriswallsmith/buzz', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    // The sylius/sylius metapackage (dev only) "replace"s the split sylius/* packages this plugin requires.
    // In this dev environment their classes therefore resolve to sylius/sylius, so the analyser reports
    // sylius/sylius as a shadow dependency and the split packages as unused. The split packages are the
    // correct production dependencies for consumers that do not install the sylius/sylius metapackage.
    ->ignoreErrorsOnPackage('sylius/sylius', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackages([
        'sylius/channel',
        'sylius/channel-bundle',
        'sylius/core',
        'sylius/core-bundle',
        'sylius/order',
        'sylius/ui-bundle',
    ], [ErrorType::UNUSED_DEPENDENCY])
;
