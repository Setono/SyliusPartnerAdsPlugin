<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Client;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Setono\SyliusPartnerAdsPlugin\Client\Client;
use Setono\SyliusPartnerAdsPlugin\Exception\RequestFailedException;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

final class ClientTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_notifies(): void
    {
        $url = 'https://example.com';

        $httpClient = $this->prophesize(HttpClientInterface::class);
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $notifyUrlProvider = $this->prophesize(NotifyUrlProviderInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $stream = $this->prophesize(StreamInterface::class);

        $notifyUrlProvider->provide(Argument::cetera())->willReturn($url);
        $requestFactory->createRequest('GET', $url)->willReturn($request->reveal());

        $stream->__toString()->willReturn('OK');
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn($stream->reveal());

        $httpClient->sendRequest($request->reveal())->willReturn($response->reveal())->shouldBeCalled();

        $client = new Client(
            $httpClient->reveal(),
            $requestFactory->reveal(),
            $notifyUrlProvider->reveal(),
        );

        $client->notify(123, 'order-123', 123.123, 123, '123.456.789.000');
    }

    #[Test]
    public function it_throws_an_exception_when_the_response_is_not_successful(): void
    {
        $url = 'https://example.com';

        $httpClient = $this->prophesize(HttpClientInterface::class);
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $notifyUrlProvider = $this->prophesize(NotifyUrlProviderInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);

        $notifyUrlProvider->provide(Argument::cetera())->willReturn($url);
        $requestFactory->createRequest('GET', $url)->willReturn($request->reveal());

        $response->getStatusCode()->willReturn(500);

        $httpClient->sendRequest($request->reveal())->willReturn($response->reveal())->shouldBeCalled();

        $client = new Client(
            $httpClient->reveal(),
            $requestFactory->reveal(),
            $notifyUrlProvider->reveal(),
        );

        $this->expectException(RequestFailedException::class);

        $client->notify(123, 'order-123', 123.123, 123, '123.456.789.000');
    }
}
