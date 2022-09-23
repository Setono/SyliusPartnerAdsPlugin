<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Dispatcher;

interface AffiliateOrderDispatcherInterface
{
    /**
     * Will dispatch affiliate orders for processing
     */
    public function dispatch(): void;
}
