<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Application\Client;

use Psr\Log\LoggerInterface;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

/**
 * Test double used by the test application so functional tests don't make real HTTP requests to Partner Ads.
 */
final class Client implements ClientInterface
{
    public function __construct(
        private readonly NotifyUrlProviderInterface $notifyUrlProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notify(int $programId, string $orderId, float $total, int $partnerId, string $ip): void
    {
        $url = $this->notifyUrlProvider->provide($programId, $orderId, $total, $partnerId, $ip);

        $this->logger->debug('Notify request: ' . $url);
    }
}
