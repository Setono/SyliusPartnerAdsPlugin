<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContext;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ProgramContextTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_returns_the_program_for_the_current_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $program = $this->prophesize(ProgramInterface::class);

        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willReturn($channel->reveal());

        $repository = $this->prophesize(ProgramRepositoryInterface::class);
        $repository->findOneByChannel($channel->reveal())->willReturn($program->reveal())->shouldBeCalled();

        $context = new ProgramContext($channelContext->reveal(), $repository->reveal());

        self::assertSame($program->reveal(), $context->getProgram());
    }

    #[Test]
    public function it_returns_null_when_no_program_is_found_for_the_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willReturn($channel->reveal());

        $repository = $this->prophesize(ProgramRepositoryInterface::class);
        $repository->findOneByChannel($channel->reveal())->willReturn(null);

        $context = new ProgramContext($channelContext->reveal(), $repository->reveal());

        self::assertNull($context->getProgram());
    }
}
