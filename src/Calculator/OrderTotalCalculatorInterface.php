<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderTotalCalculatorInterface
{
    /**
     * Returns the order total formatted for Partner Ads, which is the order total with VAT but without any fees and shipping.
     * If the order total after calculations is 57191, then this method should return 571.91.
     *
     * The amount is expressed in major units of the order's currency (minor units divided by 100). The order's
     * currency is assumed to match the currency expected by your Partner Ads program, so be mindful of this on
     * channels that use a currency different from the one configured at Partner Ads.
     */
    public function get(OrderInterface $order): float;
}
