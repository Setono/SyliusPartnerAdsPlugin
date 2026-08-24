<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class Conversion implements ConversionInterface
{
    protected ?int $id = null;

    protected ?OrderInterface $order = null;

    protected ?int $partnerId = null;

    protected string $state = ConversionInterface::STATE_PENDING;

    protected int $tries = 0;

    protected ?string $lastError = null;

    protected ?\DateTimeImmutable $notifiedAt = null;

    protected \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(int $partnerId): void
    {
        $this->partnerId = $partnerId;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getTries(): int
    {
        return $this->tries;
    }

    public function incrementTries(): void
    {
        ++$this->tries;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): void
    {
        $this->lastError = $lastError;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeImmutable $notifiedAt): void
    {
        $this->notifiedAt = $notifiedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
