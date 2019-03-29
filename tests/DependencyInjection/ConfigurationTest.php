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
    public function processes_configuration(): void
    {
        $this->assertConfigurationIsValid([
            [
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
            ]
        ]);
    }
}
