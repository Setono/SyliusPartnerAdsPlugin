<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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
use Setono\SyliusPartnerAdsPlugin\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

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

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    private LockFactory $lockFactory;

    protected function setUp(): void
    {
        $this->conversionRepository = $this->prophesize(ConversionRepositoryInterface::class);
        $this->programRepository = $this->prophesize(ProgramRepositoryInterface::class);
        $this->client = $this->prophesize(ClientInterface::class);
        $this->orderTotalCalculator = $this->prophesize(OrderTotalCalculatorInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->managerRegistry->getManagerForClass(Conversion::class)->willReturn($this->entityManager->reveal());
        $this->lockFactory = new LockFactory(new InMemoryStore());
    }

    #[Test]
    public function it_succeeds_when_there_are_no_pending_conversions(): void
    {
        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([]);

        $this->client->notify(Argument::cetera())->shouldNotBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function it_queries_the_repository_with_the_configured_notify_when(): void
    {
        $this->conversionRepository->findPending(100, NotifyWhen::Paid)->willReturn([])->shouldBeCalled();

        $commandTester = $this->getCommandTester(NotifyWhen::Paid);
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function it_does_nothing_when_another_instance_is_already_running(): void
    {
        $lock = $this->lockFactory->createLock(ProcessConversionsCommand::LOCK_RESOURCE);
        self::assertTrue($lock->acquire());

        $this->conversionRepository->findPending(Argument::cetera())->shouldNotBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('already running', $commandTester->getDisplay());
    }

    #[Test]
    public function it_releases_the_lock_when_done(): void
    {
        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([]);

        $this->getCommandTester()->execute([]);

        self::assertTrue($this->lockFactory->createLock(ProcessConversionsCommand::LOCK_RESOURCE)->acquire());
    }

    #[Test]
    public function it_notifies_partner_ads_and_marks_the_conversion_as_notified(): void
    {
        $conversion = $this->getConversion();

        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$conversion]);

        $this->client
            ->notify(123, '000000042', 571.91, 42, '127.0.0.1')
            ->shouldBeCalled()
        ;

        $this->entityManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(ConversionInterface::STATE_NOTIFIED, $conversion->getState());
        self::assertNotNull($conversion->getNotifiedAt());
        self::assertSame(0, $conversion->getTries());
    }

    #[Test]
    public function it_skips_a_conversion_when_the_order_has_already_been_notified(): void
    {
        $conversion = $this->getConversion();
        $order = $conversion->getOrder();
        self::assertNotNull($order);

        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$conversion]);
        $this->conversionRepository->hasNotifiedConversionForOrder($order)->willReturn(true);

        $this->client->notify(Argument::cetera())->shouldNotBeCalled();

        $this->entityManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(ConversionInterface::STATE_SKIPPED, $conversion->getState());
        self::assertNull($conversion->getNotifiedAt());
        self::assertStringContainsString('1 skipped', $commandTester->getDisplay());
    }

    #[Test]
    public function it_records_the_error_and_keeps_the_conversion_pending_when_notifying_fails(): void
    {
        $conversion = $this->getConversion();

        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$conversion]);

        $this->client
            ->notify(Argument::cetera())
            ->willThrow(new \RuntimeException('Something went wrong'))
        ;

        $this->entityManager->flush()->shouldBeCalled();

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

        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$conversion]);

        $this->client
            ->notify(Argument::cetera())
            ->willThrow(new \RuntimeException('Something went wrong'))
        ;

        $this->entityManager->flush()->shouldBeCalled();

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
        $this->conversionRepository->hasNotifiedConversionForOrder($order->reveal())->willReturn(false);

        $conversion = new Conversion();
        $conversion->setOrder($order->reveal());
        $conversion->setPartnerId(42);

        return $conversion;
    }

    private function getCommandTester(NotifyWhen $notifyWhen = NotifyWhen::Completed): CommandTester
    {
        return new CommandTester(new ProcessConversionsCommand(
            $this->conversionRepository->reveal(),
            $this->programRepository->reveal(),
            $this->client->reveal(),
            $this->orderTotalCalculator->reveal(),
            $this->managerRegistry->reveal(),
            $this->lockFactory,
            $notifyWhen,
        ));
    }
}
