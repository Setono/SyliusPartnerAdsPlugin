<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Client;

use Setono\SyliusPartnerAdsPlugin\Exception\RequestFailedException;

interface ClientInterface
{
    /**
     * This method will notify Partner Ads about an order
     *
     * @param int $programId The program id from Partner Ads
     * @param string $orderId The order id from Sylius
     * @param float $total The total order value
     * @param int $partnerId The Partner Ads partner id
     * @param string $ip The customer's IP
     *
     * @throws RequestFailedException
     */
    public function notify(int $programId, string $orderId, float $total, int $partnerId, string $ip): void;
}
