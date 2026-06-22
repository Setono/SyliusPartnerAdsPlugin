<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\SetonoSyliusPartnerAdsExtension;

final class SetonoSyliusPartnerAdsExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new SetonoSyliusPartnerAdsExtension()];
    }

    #[Test]
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.query_parameter', 'paid');
    }
}
