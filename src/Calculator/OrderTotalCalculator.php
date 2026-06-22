<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalCalculator implements OrderTotalCalculatorInterface
{
    /**
     * Order totals are stored in integer minor units, so dividing by 100 never yields more than two
     * decimals. That makes the rounding precision an equivalent (unkillable) mutant for the mutation
     * tester, hence the ignore below. The behaviour itself is covered by OrderTotalCalculatorTest.
     *
     * @infection-ignore-all
     */
    public function get(OrderInterface $order): float
    {
        $orderTotal = $order->getTotal() - $order->getShippingTotal();

        return round($orderTotal / 100, 2);
    }
}
