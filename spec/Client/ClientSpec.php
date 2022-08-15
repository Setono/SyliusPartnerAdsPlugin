<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\Client;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Setono\SyliusPartnerAdsPlugin\Client\Client;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

class ClientSpec extends ObjectBehavior
{
    private $url = 'https://example.com';

    public function let(HttpClientInterface $httpClient, RequestFactoryInterface $requestFactory, NotifyUrlProviderInterface $notifyUrlProvider): void
    {
        $notifyUrlProvider->provide(Argument::cetera())->willReturn($this->url);

        $this->beConstructedWith($httpClient, $requestFactory, $notifyUrlProvider);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_implements_client_interface(): void
    {
        $this->shouldHaveType(ClientInterface::class);
    }

    public function it_notifies(
        HttpClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        RequestInterface $request,
        ResponseInterface $response,
    ): void {
        $requestFactory->createRequest('GET', $this->url)->willReturn($request);

        $response->getStatusCode()->willReturn(200);
        $response->getBody()->shouldBeCalled();
        $httpClient->sendRequest($request)->willReturn($response);
        $this->notify(123, 'order-123', 123.123, 123, '123.456.789.000');
    }
}
