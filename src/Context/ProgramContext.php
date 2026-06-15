<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Context;

use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final readonly class ProgramContext implements ProgramContextInterface
{
    public function __construct(private ChannelContextInterface $channelContext, private ProgramRepositoryInterface $programRepository)
    {
    }

    public function getProgram(): ?ProgramInterface
    {
        return $this->programRepository->findOneByChannel($this->channelContext->getChannel());
    }
}
