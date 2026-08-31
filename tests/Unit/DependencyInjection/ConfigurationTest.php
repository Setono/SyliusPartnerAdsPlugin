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
                'attribution_window' => 40,
            ],
        ]);
    }

    #[Test]
    public function it_allows_an_attribution_window_of_one_day(): void
    {
        $this->assertConfigurationIsValid([
            ['attribution_window' => 1],
        ]);
    }

    #[Test]
    public function it_rejects_an_attribution_window_of_zero_days(): void
    {
        $this->assertConfigurationIsInvalid([
            ['attribution_window' => 0],
        ]);
    }

    #[Test]
    public function it_rejects_a_negative_attribution_window(): void
    {
        $this->assertConfigurationIsInvalid([
            ['attribution_window' => -1],
        ]);
    }

    #[Test]
    public function it_rejects_the_removed_cookie_node(): void
    {
        $this->assertConfigurationIsInvalid([
            ['cookie' => ['expire' => 40]],
        ]);
    }

    #[Test]
    public function it_accepts_paid_as_notify_when(): void
    {
        $this->assertConfigurationIsValid([
            ['notify_when' => 'paid'],
        ]);
    }

    #[Test]
    public function it_rejects_an_unknown_notify_when(): void
    {
        $this->assertConfigurationIsInvalid([
            ['notify_when' => 'fulfilled'],
        ]);
    }

    #[Test]
    public function it_rejects_an_empty_http_client(): void
    {
        $this->assertConfigurationIsInvalid([
            ['http_client' => ''],
        ]);
    }
}
