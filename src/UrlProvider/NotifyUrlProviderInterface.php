<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\UrlProvider;

use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;

interface NotifyUrlProviderInterface
{
    /**
     * @param int $programId
     * @param string $orderId
     * @param float $value
     * @param int $partnerId
     * @param string $ip
     *
     * @return string
     *
     * @throws MissingVariableInUrlException if any of the variables are missing
     */
    public function provide(int $programId, string $orderId, float $value, int $partnerId, string $ip): string;
}
