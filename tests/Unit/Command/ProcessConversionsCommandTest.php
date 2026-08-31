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
use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Model\Conversion;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
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
        self::assertStringContainsString('No pending conversions to process', $this->getDisplay($commandTester));
        self::assertStringNotContainsString('Processed', $this->getDisplay($commandTester));
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
    public function it_passes_the_limit_to_the_repository(): void
    {
        $this->conversionRepository->findPending(1, NotifyWhen::Completed)->willReturn([])->shouldBeCalled();

        $exitCode = $this->getCommandTester()->execute(['--limit' => '1']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function it_rejects_a_limit_below_one(): void
    {
        $this->conversionRepository->findPending(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--limit');

        $this->getCommandTester()->execute(['--limit' => '0']);
    }

    #[Test]
    public function it_rejects_max_tries_below_one(): void
    {
        $this->conversionRepository->findPending(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--max-tries');

        $this->getCommandTester()->execute(['--max-tries' => '0']);
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
        self::assertStringContainsString('already running', $this->getDisplay($commandTester));
    }

    #[Test]
    public function it_releases_the_lock_when_done(): void
    {
        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([]);

        $this->getCommandTester()->execute([]);

        self::assertTrue($this->lockFactory->createLock(ProcessConversionsCommand::LOCK_RESOURCE)->acquire());
    }

    #[Test]
    public function it_releases_the_lock_when_processing_throws(): void
    {
        $this->conversionRepository
            ->findPending(100, NotifyWhen::Completed)
            ->willThrow(new \RuntimeException('Database is down'))
        ;

        try {
            $this->getCommandTester()->execute([]);
            self::fail('Expected the exception to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('Database is down', $e->getMessage());
        }

        self::assertTrue($this->lockFactory->createLock(ProcessConversionsCommand::LOCK_RESOURCE)->acquire());
    }

    #[Test]
    public function it_notifies_partner_ads_and_marks_the_conversion_as_notified(): void
    {
        $conversion = $this->createConversion($this->createOrder($this->createChannelWithProgram()));
        $conversion->setLastError('A previous failure');

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
        self::assertNull($conversion->getLastError());
        self::assertSame(0, $conversion->getTries());
        self::assertStringContainsString(
            'Processed 1 conversion(s): 1 notified, 0 skipped, 0 failed',
            $this->getDisplay($commandTester),
        );
    }

    #[Test]
    public function it_skips_a_conversion_when_the_order_has_already_been_notified(): void
    {
        $order = $this->createOrder($this->createChannelWithProgram());
        $conversion = $this->createConversion($order);

        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$conversion]);
        $this->conversionRepository->hasNotifiedConversionForOrder($order)->willReturn(true);

        $this->client->notify(Argument::cetera())->shouldNotBeCalled();

        $this->entityManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(ConversionInterface::STATE_SKIPPED, $conversion->getState());
        self::assertNull($conversion->getNotifiedAt());
        self::assertStringContainsString(
            'Processed 1 conversion(s): 0 notified, 1 skipped, 0 failed',
            $this->getDisplay($commandTester),
        );
    }

    #[Test]
    public function it_continues_with_the_next_conversion_after_skipping_one(): void
    {
        $channel = $this->createChannelWithProgram();

        $duplicateOrder = $this->createOrder($channel);
        $duplicate = $this->createConversion($duplicateOrder);
        $this->conversionRepository->hasNotifiedConversionForOrder($duplicateOrder)->willReturn(true);

        $conversion = $this->createConversion($this->createOrder($channel));

        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$duplicate, $conversion]);

        $this->client->notify(Argument::cetera())->shouldBeCalledTimes(1);

        $this->entityManager->flush()->shouldBeCalledTimes(2);

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(ConversionInterface::STATE_SKIPPED, $duplicate->getState());
        self::assertSame(ConversionInterface::STATE_NOTIFIED, $conversion->getState());
        self::assertStringContainsString(
            'Processed 2 conversion(s): 1 notified, 1 skipped, 0 failed',
            $this->getDisplay($commandTester),
        );
    }

    #[Test]
    public function it_records_the_error_and_keeps_the_conversion_pending_when_notifying_fails(): void
    {
        $conversion = $this->createConversion($this->createOrder($this->createChannelWithProgram()));

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
        self::assertStringContainsString('failed: Something went wrong', $this->getDisplay($commandTester));
        self::assertStringContainsString(
            'Processed 1 conversion(s): 0 notified, 0 skipped, 1 failed',
            $this->getDisplay($commandTester),
        );
    }

    #[Test]
    public function it_marks_the_conversion_as_failed_when_max_tries_is_reached(): void
    {
        $conversion = $this->createConversion($this->createOrder($this->createChannelWithProgram()));

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

    #[Test]
    public function it_fails_when_the_conversion_has_no_order(): void
    {
        $this->assertNotifyingFails(
            $this->createConversion(null),
            'The conversion does not have an order associated',
        );
    }

    #[Test]
    public function it_fails_when_the_order_has_no_channel(): void
    {
        $this->assertNotifyingFails(
            $this->createConversion($this->createOrder(null)),
            'The order does not have a channel associated',
        );
    }

    #[Test]
    public function it_fails_when_no_program_exists_for_the_channel(): void
    {
        $channel = $this->createChannel();
        $this->programRepository->findOneByChannel($channel)->willReturn(null);

        $this->assertNotifyingFails(
            $this->createConversion($this->createOrder($channel)),
            'No enabled program with a program id exists for the channel "FASHION_WEB"',
        );
    }

    #[Test]
    public function it_fails_when_the_program_has_no_program_id(): void
    {
        $this->assertNotifyingFails(
            $this->createConversion($this->createOrder($this->createChannelWithProgram(null))),
            'No enabled program with a program id exists for the channel "FASHION_WEB"',
        );
    }

    #[Test]
    public function it_fails_when_the_conversion_has_no_partner_id(): void
    {
        $this->assertNotifyingFails(
            $this->createConversion($this->createOrder($this->createChannelWithProgram()), null),
            'The conversion does not have a valid partner id',
        );
    }

    #[Test]
    public function it_fails_when_the_partner_id_is_not_positive(): void
    {
        $this->assertNotifyingFails(
            $this->createConversion($this->createOrder($this->createChannelWithProgram()), 0),
            'The conversion does not have a valid partner id',
        );
    }

    private function assertNotifyingFails(ConversionInterface $conversion, string $expectedError): void
    {
        $this->conversionRepository->findPending(100, NotifyWhen::Completed)->willReturn([$conversion]);

        $this->client->notify(Argument::cetera())->shouldNotBeCalled();

        $this->entityManager->flush()->shouldBeCalled();

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(ConversionInterface::STATE_PENDING, $conversion->getState());
        self::assertSame(1, $conversion->getTries());
        self::assertSame($expectedError, $conversion->getLastError());
    }

    private function createChannel(): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('FASHION_WEB');

        return $channel->reveal();
    }

    private function createChannelWithProgram(?int $programId = 123): ChannelInterface
    {
        $channel = $this->createChannel();

        $program = $this->prophesize(ProgramInterface::class);
        $program->getProgramId()->willReturn($programId);

        $this->programRepository->findOneByChannel($channel)->willReturn($program->reveal());

        return $channel;
    }

    private function createOrder(?ChannelInterface $channel): OrderInterface
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);
        $order->getNumber()->willReturn('000000042');
        $order->getCustomerIp()->willReturn('127.0.0.1');

        $this->orderTotalCalculator->get($order->reveal())->willReturn(571.91);
        $this->conversionRepository->hasNotifiedConversionForOrder($order->reveal())->willReturn(false);

        return $order->reveal();
    }

    private function createConversion(?OrderInterface $order, ?int $partnerId = 42): ConversionInterface
    {
        $conversion = new Conversion();

        if (null !== $order) {
            $conversion->setOrder($order);
        }

        if (null !== $partnerId) {
            $conversion->setPartnerId($partnerId);
        }

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

    /**
     * SymfonyStyle wraps long lines, so collapse all whitespace before asserting on the output
     */
    private function getDisplay(CommandTester $commandTester): string
    {
        return (string) preg_replace('/\s+/', ' ', $commandTester->getDisplay());
    }
}
