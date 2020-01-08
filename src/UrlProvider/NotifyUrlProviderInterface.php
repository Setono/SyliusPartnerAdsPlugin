<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\UrlProvider;

use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;

interface NotifyUrlProviderInterface
{
    /**
     * @throws MissingVariableInUrlException if any of the variables are missing
     */
    public function provide(int $programId, string $orderId, float $value, int $partnerId, string $ip): string;
}
