<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;

class AffiliateOrder implements AffiliateOrderInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?ProgramInterface $program = null;

    protected ?OrderInterface $order = null;

    protected ?int $partner = null;

    protected ?string $ip = null;

    protected string $state = self::STATE_INITIAL;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgram(): ?ProgramInterface
    {
        return $this->program;
    }

    public function setProgram(ProgramInterface $program): void
    {
        $this->program = $program;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getPartner(): ?int
    {
        return $this->partner;
    }

    public function setPartner(int $partner): void
    {
        $this->partner = $partner;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }
}
