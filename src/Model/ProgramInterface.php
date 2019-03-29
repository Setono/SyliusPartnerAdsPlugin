<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface ProgramInterface extends ResourceInterface, ToggleableInterface
{
    public function getId(): ?int;

    public function getProgramId(): ?int;

    public function setProgramId(int $programId): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(ChannelInterface $channel): void;
}
