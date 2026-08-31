<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Client;

use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Setono\SyliusPartnerAdsPlugin\Exception\RequestFailedException;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

final readonly class Client implements ClientInterface
{
    public function __construct(private HttpClientInterface $httpClient, private RequestFactoryInterface $requestFactory, private NotifyUrlProviderInterface $notifyUrlProvider)
    {
    }

    public function notify(int $programId, string $orderId, float $total, int $partnerId, string $ip): void
    {
        $url = $this->notifyUrlProvider->provide($programId, $orderId, $total, $partnerId, $ip);

        $this->sendRequest('GET', $url);
    }

    private function sendRequest(string $method, string $url): string
    {
        $request = $this->requestFactory->createRequest($method, $url);

        $response = $this->httpClient->sendRequest($request);

        // any 2xx counts as success - Partner Ads answers 200 today, but a 204 must not be treated as a failure
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RequestFailedException($request, $response, $statusCode);
        }

        return (string) $response->getBody();
    }
}
