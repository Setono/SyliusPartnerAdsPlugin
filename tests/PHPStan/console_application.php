<?php

declare(strict_types=1);

use Setono\SyliusPartnerAdsPlugin\Tests\Application\Kernel;
use Sylius\Bundle\AdminBundle\SyliusAdminBundle;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require __DIR__ . '/../Application/config/bootstrap.php';

// The static-code-analysis CI job removes sylius/sylius before running PHPStan, and without it the test
// application cannot boot (it registers the admin, shop, and API bundles among others). PHPStan only uses
// this loader to resolve command options and arguments, so degrade to an empty console application instead
// of aborting the whole analysis with an internal error.
if (!class_exists(SyliusAdminBundle::class)) {
    return new Symfony\Component\Console\Application();
}

$kernel = new Kernel('test', true);
$kernel->boot();

return new Application($kernel);
