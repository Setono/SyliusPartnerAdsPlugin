<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Program implements ProgramInterface
{
    use ToggleableTrait;

    protected int $id;

    protected int $programId;

    protected ?ChannelInterface $channel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramId(): ?int
    {
        return $this->programId;
    }

    public function setProgramId(int $programId): void
    {
        $this->programId = $programId;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }
}
