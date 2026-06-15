<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    #[Test]
    public function it_processes_configuration(): void
    {
        $this->assertConfigurationIsValid([
            [
                'query_parameter' => 'paid',
                'cookie' => [
                    'name' => 'setono_sylius_partner_ads_cookie',
                    'expire' => 40,
                ],
            ],
        ]);
    }
}
