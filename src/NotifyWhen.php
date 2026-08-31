<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin;

/**
 * Decides which orders' conversions are eligible to be sent to Partner Ads
 */
enum NotifyWhen: string
{
    /**
     * Notify Partner Ads as soon as the customer has completed the checkout, i.e. when the order is placed.
     * This is how affiliate networks usually work: the sale is tracked immediately and you cancel it
     * with Partner Ads later if the order is never paid.
     */
    case Completed = 'completed';

    /**
     * Notify Partner Ads only when the order has been fully paid
     */
    case Paid = 'paid';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
