<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalCalculator implements OrderTotalCalculatorInterface
{
    public function calculate(OrderInterface $order): int
    {
        return $order->getTotal() - $order->getShippingTotal();
    }
}
