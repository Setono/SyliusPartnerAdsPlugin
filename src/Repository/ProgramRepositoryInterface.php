<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Repository;

use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ProgramRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the program that is enabled on the given channel or null if no account is enabled for the given channel
     *
     * @param ChannelInterface $channel
     *
     * @return ProgramInterface|null
     */
    public function findOneByChannel(ChannelInterface $channel): ?ProgramInterface;
}
