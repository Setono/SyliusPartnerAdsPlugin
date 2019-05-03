<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\UrlProvider;

use PhpSpec\ObjectBehavior;
use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProvider;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

class NotifyUrlProviderSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith('https://example.com/?programid={program_id}&type=salg&partnerid={partner_id}&userip={ip}&ordreid={order_id}&varenummer=x&antal=1&omprsalg={value}');
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NotifyUrlProvider::class);
    }

    public function it_implements_notify_url_provider_interface(): void
    {
        $this->shouldImplement(NotifyUrlProviderInterface::class);
    }

    public function it_throws_exception(): void
    {
        $this->beConstructedWith('');

        $this->shouldThrow(MissingVariableInUrlException::class)->during('provide', [123, '123', 123.123, 123, '123']);
    }

    public function it_provides_correct_url(): void
    {
        $programId = 123;
        $partnerId = 456;
        $ip = '123.123.123.123';
        $orderId = 'order-123';
        $value = 123.123;

        $expectedUrl = "https://example.com/?programid=$programId&type=salg&partnerid=$partnerId&userip=$ip&ordreid=$orderId&varenummer=x&antal=1&omprsalg=$value";

        $this->provide($programId, $orderId, $value, $partnerId, $ip)->shouldReturn($expectedUrl);
    }
}
