<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Functional;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Setono\SyliusPartnerAdsPlugin\Tests\Application\Kernel;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Exercises the repository against a real database with the schema in place - the functional-tests CI job provides
 * both. Without a reachable database (e.g. in the unit-tests and mutation-tests jobs) the test is skipped.
 *
 * Every test runs inside a transaction that is rolled back afterwards (see the DAMA Doctrine test bundle configured
 * in phpunit.xml.dist), so nothing is left behind in the database.
 */
final class ConversionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private ConversionRepositoryInterface $repository;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        try {
            $entityManager->getConnection()->executeQuery('SELECT 1');
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('No database available: %s', $e->getMessage()));
        }

        $this->entityManager = $entityManager;

        $repository = self::getContainer()->get('setono_sylius_partner_ads.repository.conversion');
        \assert($repository instanceof ConversionRepositoryInterface);
        $this->repository = $repository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // see AdminRoutingTest
        restore_exception_handler();
    }

    #[Test]
    public function it_finds_pending_conversions_for_completed_orders(): void
    {
        $awaitingPayment = $this->createConversion($this->createOrder(OrderPaymentStates::STATE_AWAITING_PAYMENT));
        $paid = $this->createConversion($this->createOrder(OrderPaymentStates::STATE_PAID));

        // not eligible: the checkout has not been completed
        $this->createConversion($this->createOrder(OrderPaymentStates::STATE_CART, OrderCheckoutStates::STATE_CART));
        // not eligible: the order has been cancelled
        $this->createConversion($this->createOrder(OrderPaymentStates::STATE_CANCELLED, orderState: OrderInterface::STATE_CANCELLED));
        // not eligible: already notified
        $this->createConversion($this->createOrder(OrderPaymentStates::STATE_PAID), ConversionInterface::STATE_NOTIFIED);

        $this->entityManager->flush();

        self::assertSame(self::ids($awaitingPayment, $paid), self::ids(...$this->repository->findPending(10, NotifyWhen::Completed)));
    }

    #[Test]
    public function it_only_finds_conversions_for_paid_orders_when_notifying_when_paid(): void
    {
        $this->createConversion($this->createOrder(OrderPaymentStates::STATE_AWAITING_PAYMENT));
        $paid = $this->createConversion($this->createOrder(OrderPaymentStates::STATE_PAID));
        // paid, but cancelled afterwards
        $this->createConversion($this->createOrder(OrderPaymentStates::STATE_PAID, orderState: OrderInterface::STATE_CANCELLED));

        $this->entityManager->flush();

        self::assertSame(self::ids($paid), self::ids(...$this->repository->findPending(10, NotifyWhen::Paid)));
    }

    #[Test]
    public function it_limits_the_number_of_pending_conversions_and_returns_the_oldest_first(): void
    {
        $first = $this->createConversion($this->createOrder(OrderPaymentStates::STATE_PAID));
        $this->createConversion($this->createOrder(OrderPaymentStates::STATE_PAID));

        $this->entityManager->flush();

        self::assertSame(self::ids($first), self::ids(...$this->repository->findPending(1, NotifyWhen::Completed)));
    }

    #[Test]
    public function it_finds_the_first_conversion_for_an_order(): void
    {
        $order = $this->createOrder(OrderPaymentStates::STATE_PAID);
        $first = $this->createConversion($order);
        $this->createConversion($order);

        $orderWithoutConversion = $this->createOrder(OrderPaymentStates::STATE_PAID);

        $this->entityManager->flush();

        self::assertSame($first->getId(), $this->repository->findOneByOrder($order)?->getId());
        self::assertNull($this->repository->findOneByOrder($orderWithoutConversion)?->getId());
    }

    #[Test]
    public function it_knows_whether_an_order_has_a_notified_conversion(): void
    {
        $order = $this->createOrder(OrderPaymentStates::STATE_PAID);
        $this->createConversion($order);
        $this->entityManager->flush();

        self::assertFalse($this->repository->hasNotifiedConversionForOrder($order));

        $this->createConversion($order, ConversionInterface::STATE_NOTIFIED);
        $this->entityManager->flush();

        self::assertTrue($this->repository->hasNotifiedConversionForOrder($order));

        // still true - and still a single-row lookup - with more than one notified conversion
        $this->createConversion($order, ConversionInterface::STATE_NOTIFIED);
        $this->entityManager->flush();

        self::assertTrue($this->repository->hasNotifiedConversionForOrder($order));
    }

    /**
     * Assertions compare ids rather than entities: on failure PHPUnit would otherwise export the entire order object
     * graph, which is extremely slow.
     *
     * @return list<int|null>
     */
    private static function ids(ConversionInterface ...$conversions): array
    {
        return array_values(array_map(static fn (ConversionInterface $conversion): ?int => $conversion->getId(), $conversions));
    }

    private function createOrder(
        string $paymentState,
        string $checkoutState = OrderCheckoutStates::STATE_COMPLETED,
        string $orderState = OrderInterface::STATE_NEW,
    ): OrderInterface {
        $factory = self::getContainer()->get('sylius.factory.order');
        \assert($factory instanceof FactoryInterface);

        $order = $factory->createNew();
        \assert($order instanceof OrderInterface);

        $order->setCurrencyCode('DKK');
        $order->setLocaleCode('en_US');
        $order->setCheckoutState($checkoutState);
        $order->setPaymentState($paymentState);
        $order->setState($orderState);

        $this->entityManager->persist($order);

        return $order;
    }

    private function createConversion(OrderInterface $order, string $state = ConversionInterface::STATE_PENDING): ConversionInterface
    {
        $factory = self::getContainer()->get('setono_sylius_partner_ads.factory.conversion');
        \assert($factory instanceof FactoryInterface);

        $conversion = $factory->createNew();
        \assert($conversion instanceof ConversionInterface);

        $conversion->setOrder($order);
        $conversion->setPartnerId(42);
        $conversion->setState($state);

        $this->entityManager->persist($conversion);

        return $conversion;
    }
}
