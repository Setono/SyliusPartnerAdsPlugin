<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Processor;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;

interface AffiliateOrderProcessorInterface
{
    public function process(AffiliateOrderInterface $affiliateOrder): void;
}
