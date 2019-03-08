<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @test
     */
    public function values_are_invalid_if_required_value_is_not_provided(): void
    {
        $this->assertConfigurationIsInvalid([[]], 'program_id');
    }

    /**
     * @test
     */
    public function processes_configuration(): void
    {
        $expected = [
            'program_id' => 9876,
            'query_parameter' => 'paid',
            'urls' => [
                'notify' => 'https://www.partner-ads.com/dk/leadtracks2s.php?programid=$program_id&type=salg&partnerid=$partner_id&userip=$ip&ordreid=$order_id&varenummer=x&antal=1&omprsalg=$order_total'
            ],
            'cookie' => [
                'name' => 'setono_sylius_partner_ads_cookie',
                'expire' => 40
            ],
            'messenger' => [
                'enabled' => true,
            ]
        ];

        $this->assertProcessedConfigurationEquals([
            ['program_id' => 1234],
            ['program_id' => 9876],
        ], $expected);
    }
}
