<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\Tests\Application\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminRoutingTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Booting the kernel (with the DebugBundle enabled in the test environment) leaves an
        // exception handler registered; PHPUnit 11 reports the test as risky if it is not restored.
        restore_exception_handler();
    }

    #[Test]
    public function the_admin_program_routes_are_registered(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');
        $routes = $router->getRouteCollection();

        // The presence of these routes proves the `type: sylius.resource` routing resolves,
        // which in turn requires the resource and its referenced grid to be registered.
        self::assertNotNull(
            $routes->get('setono_sylius_partner_ads_admin_program_index'),
            'The admin program index route should be registered through the sylius.resource routing.',
        );
        self::assertNotNull($routes->get('setono_sylius_partner_ads_admin_program_create'));
        self::assertNotNull($routes->get('setono_sylius_partner_ads_admin_program_update'));
    }

    #[Test]
    public function the_admin_conversion_routes_are_registered(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');
        $routes = $router->getRouteCollection();

        self::assertNotNull(
            $routes->get('setono_sylius_partner_ads_admin_conversion_index'),
            'The admin conversion index route should be registered through the sylius.resource routing.',
        );
        self::assertNotNull($routes->get('setono_sylius_partner_ads_admin_conversion_delete'));

        // conversions are created by the system, so they must not be creatable or editable in the admin
        self::assertNull($routes->get('setono_sylius_partner_ads_admin_conversion_create'));
        self::assertNull($routes->get('setono_sylius_partner_ads_admin_conversion_update'));
    }
}
