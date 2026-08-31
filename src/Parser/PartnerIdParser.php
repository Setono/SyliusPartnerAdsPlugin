<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Parser;

final class PartnerIdParser
{
    /**
     * Returns the Partner Ads partner id held by a raw value - the affiliate query parameter or the value stored
     * for the visitor - or null if the value is not a positive integer.
     *
     * Anything else must never be treated as a partner id: an empty or mangled value (a tracker rewriting the
     * affiliate link, a hand-edited stored value) would otherwise be cast to partner id 0, overwriting a legitimate
     * attribution and later being reported to Partner Ads as partner 0. Arrays (?paid[]=x) are rejected here too,
     * so callers can read the raw value without InputBag::get() throwing a 400 for them.
     */
    public static function parse(mixed $value): ?int
    {
        $partnerId = filter_var($value, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return false === $partnerId ? null : $partnerId;
    }
}
