<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalCalculator implements OrderTotalCalculatorInterface
{
    public function get(OrderInterface $order): float
    {
        $orderTotal = $order->getTotal() - $order->getShippingTotal();

        // The total is in minor units (an integer), so dividing by 100 always yields at most two
        // decimals. Formatting with two decimals normalises the float representation for Partner Ads.
        return (float) sprintf('%.2f', $orderTotal / 100);
    }
}
