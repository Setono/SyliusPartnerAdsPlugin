<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Notifier;

interface NotifierInterface
{
    public function notify(string $orderId, float $orderTotal, string $partnerId, string $ip): void;
}
