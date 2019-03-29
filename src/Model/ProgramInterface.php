<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface ProgramInterface extends ResourceInterface, ToggleableInterface, ChannelAwareInterface
{
    public function getId(): ?int;

    public function getProgramId(): ?int;

    public function setProgramId(int $programId): void;
}
