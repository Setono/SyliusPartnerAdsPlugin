<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderTotalCalculatorInterface
{
    /**
     * Returns the order total for Partner Ads, which is order total with vat but without any fees or shipping
     */
    public function calculate(OrderInterface $order): int;
}
