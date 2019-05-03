<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderTotalCalculatorInterface
{
    /**
     * Returns the order total formatted for Partner Ads, which is order total with vat but without any fees and shipping
     * If the order total after calculations is 57191, then this method should return 571.91
     *
     * @param OrderInterface $order
     *
     * @return float
     */
    public function get(OrderInterface $order): float;
}
