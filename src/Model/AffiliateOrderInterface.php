<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

// todo implement versioned interface
interface AffiliateOrderInterface extends ResourceInterface, TimestampableInterface
{
    public const STATE_FAILED = 'failed';

    public const STATE_INITIAL = 'initial';

    public const STATE_PENDING = 'pending';

    public const STATE_PROCESSING = 'processing';

    public const STATE_SENT = 'sent';

    public function getId(): ?int;

    public function getProgram(): ?ProgramInterface;

    public function setProgram(ProgramInterface $program): void;

    public function getOrder(): ?OrderInterface;

    public function setOrder(OrderInterface $order): void;

    /**
     * The affiliates partner id with Partner Ads
     */
    public function getPartner(): ?int;

    public function setPartner(int $partner): void;

    public function getIp(): ?string;

    public function setIp(string $ip): void;

    public function getState(): string;

    public function setState(string $state): void;
}
