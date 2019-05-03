<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\SetonoSyliusPartnerAdsExtension;

final class SetonoSyliusPartnerAdsExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new SetonoSyliusPartnerAdsExtension()];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameter_has_been_set()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.query_parameter', 'paid');
    }
}
