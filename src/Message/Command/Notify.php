<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Message\Command;

final class Notify
{
    private int $programId;

    private string $orderId;

    private float $orderTotal;

    private int $partnerId;

    private string $ip;

    public function __construct(int $programId, string $orderId, float $orderTotal, int $partnerId, string $ip)
    {
        $this->programId = $programId;
        $this->orderId = $orderId;
        $this->orderTotal = $orderTotal;
        $this->partnerId = $partnerId;
        $this->ip = $ip;
    }

    public function getProgramId(): int
    {
        return $this->programId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getOrderTotal(): float
    {
        return $this->orderTotal;
    }

    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    public function getIp(): string
    {
        return $this->ip;
    }
}
