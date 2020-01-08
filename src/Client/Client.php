<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Setono\SyliusPartnerAdsPlugin\Exception\RequestFailedException;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

final class Client implements ClientInterface
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var NotifyUrlProviderInterface */
    private $notifyUrlProvider;

    public function __construct(
        HttpClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        NotifyUrlProviderInterface $notifyUrlProvider
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->notifyUrlProvider = $notifyUrlProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ClientExceptionInterface
     * @throws RequestFailedException
     */
    public function notify(int $programId, string $orderId, float $total, int $partnerId, string $ip): void
    {
        $url = $this->notifyUrlProvider->provide($programId, $orderId, $total, $partnerId, $ip);

        $this->sendRequest('GET', $url);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RequestFailedException
     */
    private function sendRequest(string $method, string $url): string
    {
        $request = $this->requestFactory->createRequest($method, $url);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new RequestFailedException($request, $response, $response->getStatusCode());
        }

        return (string) $response->getBody();
    }
}
