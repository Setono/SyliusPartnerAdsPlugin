<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

return (new Configuration())
    // Scan the PHP service configuration so symbols only referenced there (e.g. php-http/discovery) are detected.
    ->addPathToScan(__DIR__ . '/config', false)
    ->addPathToExclude(__DIR__ . '/tests')
;
