<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\UrlProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProvider;

final class NotifyUrlProviderTest extends TestCase
{
    #[Test]
    public function it_provides_correct_url(): void
    {
        $provider = new NotifyUrlProvider(
            'https://example.com/?programid={program_id}&type=salg&partnerid={partner_id}&userip={ip}&ordreid={order_id}&varenummer=x&antal=1&omprsalg={value}',
        );

        $programId = 123;
        $partnerId = 456;
        $ip = '123.123.123.123';
        $orderId = 'order-123';
        $value = 123.123;

        $expectedUrl = "https://example.com/?programid=$programId&type=salg&partnerid=$partnerId&userip=$ip&ordreid=$orderId&varenummer=x&antal=1&omprsalg=$value";

        self::assertSame($expectedUrl, $provider->provide($programId, $orderId, $value, $partnerId, $ip));
    }

    #[Test]
    public function it_throws_an_exception_when_a_variable_is_missing(): void
    {
        $provider = new NotifyUrlProvider('');

        $this->expectException(MissingVariableInUrlException::class);

        $provider->provide(123, '123', 123.123, 123, '123');
    }
}
