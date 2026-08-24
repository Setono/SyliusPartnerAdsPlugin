<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Command;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Command\ProcessConversionsCommand;
use Setono\SyliusPartnerAdsPlugin\Model\Conversion;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessConversionsCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ConversionRepositoryInterface> */
    private ObjectProphecy $conversionRepository;

    /** @var ObjectProphecy<ProgramRepositoryInterface> */
    private ObjectProphecy $programRepository;

    /** @var ObjectProphecy<ClientInterface> */
    private ObjectProphecy $client;

    /** @var ObjectProphecy<OrderTotalCalculatorInterface> */
    private ObjectProphecy $orderTotalCalculator;

    /** @var ObjectProphecy<ObjectManager> */
    private ObjectProphecy $conversionManager;

    protected function setUp(): void
    {
        $this->conversionRepository = $this->prophesize(ConversionRepositoryInterface::class);
        $this->programRepository = $this->prophesize(ProgramRepositoryInterface::class);
        $this->client = $this->prophesize(ClientInterface::class);
        $this->orderTotalCalculator = $this->prophesize(OrderTotalCalculatorInterface::class);
        $this->conversionManager = $this->prophesize(ObjectManager::class);
    }

    #[Test]
    public function it_succeeds_when_there_are_no_pending_conversions(): void
    {
        $this->conversionRepository->findPendingForPaidOrders(100)->willReturn([]);

        $this->client->notify(Argument::cetera())->shouldNotBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function it_notifies_partner_ads_and_marks_the_conversion_as_notified(): void
    {
        $conversion = $this->getConversion();

        $this->conversionRepository->findPendingForPaidOrders(100)->willReturn([$conversion]);

        $this->client
            ->notify(123, '000000042', 571.91, 42, '127.0.0.1')
            ->shouldBeCalled()
        ;

        $this->conversionManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(ConversionInterface::STATE_NOTIFIED, $conversion->getState());
        self::assertNotNull($conversion->getNotifiedAt());
        self::assertSame(0, $conversion->getTries());
    }

    #[Test]
    public function it_records_the_error_and_keeps_the_conversion_pending_when_notifying_fails(): void
    {
        $conversion = $this->getConversion();

        $this->conversionRepository->findPendingForPaidOrders(100)->willReturn([$conversion]);

        $this->client
            ->notify(Argument::cetera())
            ->willThrow(new \RuntimeException('Something went wrong'))
        ;

        $this->conversionManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(ConversionInterface::STATE_PENDING, $conversion->getState());
        self::assertSame(1, $conversion->getTries());
        self::assertSame('Something went wrong', $conversion->getLastError());
    }

    #[Test]
    public function it_marks_the_conversion_as_failed_when_max_tries_is_reached(): void
    {
        $conversion = $this->getConversion();

        $this->conversionRepository->findPendingForPaidOrders(100)->willReturn([$conversion]);

        $this->client
            ->notify(Argument::cetera())
            ->willThrow(new \RuntimeException('Something went wrong'))
        ;

        $this->conversionManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--max-tries' => '1']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(ConversionInterface::STATE_FAILED, $conversion->getState());
        self::assertSame(1, $conversion->getTries());
    }

    private function getConversion(): ConversionInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('FASHION_WEB');

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel->reveal());
        $order->getNumber()->willReturn('000000042');
        $order->getCustomerIp()->willReturn('127.0.0.1');

        $program = $this->prophesize(ProgramInterface::class);
        $program->getProgramId()->willReturn(123);

        $this->programRepository->findOneByChannel($channel->reveal())->willReturn($program->reveal());
        $this->orderTotalCalculator->get($order->reveal())->willReturn(571.91);

        $conversion = new Conversion();
        $conversion->setOrder($order->reveal());
        $conversion->setPartnerId(42);

        return $conversion;
    }

    private function getCommandTester(): CommandTester
    {
        return new CommandTester(new ProcessConversionsCommand(
            $this->conversionRepository->reveal(),
            $this->programRepository->reveal(),
            $this->client->reveal(),
            $this->orderTotalCalculator->reveal(),
            $this->conversionManager->reveal(),
        ));
    }
}
