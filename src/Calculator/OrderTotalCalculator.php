<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalCalculator implements OrderTotalCalculatorInterface
{
    public function get(OrderInterface $order): string
    {
        $orderTotal = $order->getTotal() - $order->getShippingTotal();

        return sprintf('%01.2f', $orderTotal / 100);
    }
}
