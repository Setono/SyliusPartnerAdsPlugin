<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Context;

use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final class ProgramContext implements ProgramContextInterface
{
    /**
     * @var ChannelContextInterface
     */
    private $channelContext;

    /**
     * @var ProgramRepositoryInterface
     */
    private $programRepository;

    public function __construct(ChannelContextInterface $channelContext, ProgramRepositoryInterface $programRepository)
    {
        $this->channelContext = $channelContext;
        $this->programRepository = $programRepository;
    }

    public function getProgram(): ?ProgramInterface
    {
        return $this->programRepository->findOneByChannel($this->channelContext->getChannel());
    }
}
