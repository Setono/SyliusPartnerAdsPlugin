<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ConversionInterface extends ResourceInterface
{
    public const STATE_PENDING = 'pending';

    public const STATE_NOTIFIED = 'notified';

    public const STATE_FAILED = 'failed';

    public function getId(): ?int;

    public function getOrder(): ?OrderInterface;

    public function setOrder(OrderInterface $order): void;

    /**
     * The Partner Ads partner id that referred the order
     */
    public function getPartnerId(): ?int;

    public function setPartnerId(int $partnerId): void;

    public function getState(): string;

    public function setState(string $state): void;

    /**
     * The number of unsuccessful attempts to notify Partner Ads about this conversion
     */
    public function getTries(): int;

    public function incrementTries(): void;

    public function getLastError(): ?string;

    public function setLastError(?string $lastError): void;

    public function getNotifiedAt(): ?\DateTimeImmutable;

    public function setNotifiedAt(?\DateTimeImmutable $notifiedAt): void;

    public function getCreatedAt(): \DateTimeImmutable;
}
