<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalCalculator implements OrderTotalCalculatorInterface
{
    public function get(OrderInterface $order): float
    {
        $orderTotal = $order->getTotal() - $order->getShippingTotal();

        return round($orderTotal / 100, 2);
    }
}
