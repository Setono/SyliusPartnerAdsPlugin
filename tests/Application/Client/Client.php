<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\Application\Client;

use Psr\Log\LoggerInterface;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

final class Client implements ClientInterface
{
    /** @var NotifyUrlProviderInterface */
    private $notifyUrlProvider;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(NotifyUrlProviderInterface $notifyUrlProvider, LoggerInterface $logger)
    {
        $this->notifyUrlProvider = $notifyUrlProvider;
        $this->logger = $logger;
    }

    public function notify(int $programId, string $orderId, float $total, int $partnerId, string $ip): void
    {
        $url = $this->notifyUrlProvider->provide($programId, $orderId, $total, $partnerId, $ip);

        $this->logger->debug('Notify request: ' . $url);
    }
}
