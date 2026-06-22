<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // Buzz is only used when its optional dev dependency is installed (see below), so its ignore does not
    // apply when only production dependencies are analysed.
    ->disableReportingUnmatchedIgnores()
    // Scan the PHP service configuration so symbols only referenced there (e.g. php-http/discovery) are detected.
    ->addPathToScan(__DIR__ . '/config', false)
    ->addPathToExclude(__DIR__ . '/tests')
    // Buzz is an optional PSR-18 client fallback referenced behind interface_exists()/class_exists() guards,
    // so it is intentionally a dev dependency and may not be installed when only production deps are analysed.
    ->ignoreErrorsOnPackage('kriswallsmith/buzz', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreUnknownClasses([
        \Buzz\Client\BuzzClientInterface::class,
        \Buzz\Client\Curl::class,
    ])
;
