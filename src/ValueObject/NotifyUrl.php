<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\ValueObject;

use Webmozart\Assert\Assert;

final class NotifyUrl
{
    private int $programId;

    private int $partnerId;

    private string $ip;

    private string $orderNumber;

    private int $orderTotal;

    public function __construct(int $programId, int $partnerId, string $ip, string $orderNumber, int $orderTotal)
    {
        Assert::greaterThanEq($programId, 1);
        Assert::greaterThanEq($partnerId, 1);
        Assert::greaterThanEq($orderTotal, 1);

        $this->programId = $programId;
        $this->partnerId = $partnerId;
        $this->ip = $ip;
        $this->orderNumber = $orderNumber;
        $this->orderTotal = $orderTotal;
    }

    public function value(): string
    {
        return sprintf(
            'https://www.partner-ads.com/dk/leadtracks2s.php?programid=%d&type=salg&partnerid=%d&userip=%s&ordreid=%s&varenummer=x&antal=1&omprsalg=%F',
            $this->programId,
            $this->partnerId,
            $this->ip,
            $this->orderNumber,
            round($this->orderTotal / 100, 2)
        );
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
