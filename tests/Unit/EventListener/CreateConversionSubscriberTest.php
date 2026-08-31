<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusPartnerAdsPlugin\EventListener\CreateConversionSubscriber;
use Setono\SyliusPartnerAdsPlugin\Model\Conversion;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\PartnerIdStorage\PartnerIdStorageInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class CreateConversionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<PartnerIdStorageInterface> */
    private ObjectProphecy $partnerIdStorage;

    /** @var ObjectProphecy<FactoryInterface> */
    private ObjectProphecy $conversionFactory;

    /** @var ObjectProphecy<ConversionRepositoryInterface> */
    private ObjectProphecy $conversionRepository;

    protected function setUp(): void
    {
        $this->partnerIdStorage = $this->prophesize(PartnerIdStorageInterface::class);
        $this->conversionFactory = $this->prophesize(FactoryInterface::class);
        $this->conversionRepository = $this->prophesize(ConversionRepositoryInterface::class);
    }

    #[Test]
    public function it_subscribes_to_the_order_post_complete_event(): void
    {
        self::assertSame(
            ['sylius.order.post_complete' => 'createConversion'],
            CreateConversionSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function it_creates_a_conversion_when_the_order_was_referred_by_a_partner(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->partnerIdStorage->get()->willReturn(42);

        $this->conversionRepository->findOneByOrder($order->reveal())->willReturn(null);

        $conversion = new Conversion();
        $this->conversionFactory->createNew()->willReturn($conversion);

        $this->conversionRepository->add($conversion)->shouldBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));

        self::assertSame($order->reveal(), $conversion->getOrder());
        self::assertSame(42, $conversion->getPartnerId());
        self::assertSame(ConversionInterface::STATE_PENDING, $conversion->getState());
    }

    #[Test]
    public function it_does_nothing_when_the_subject_is_not_an_order(): void
    {
        $this->partnerIdStorage->get()->shouldNotBeCalled();
        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent(new \stdClass()));
    }

    #[Test]
    public function it_does_nothing_when_no_partner_id_is_stored(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->partnerIdStorage->get()->willReturn(null);

        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    #[Test]
    public function it_does_nothing_when_the_order_already_has_a_conversion(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->partnerIdStorage->get()->willReturn(42);

        $this->conversionRepository->findOneByOrder($order->reveal())->willReturn(new Conversion());
        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    #[Test]
    public function it_fails_when_the_factory_does_not_create_a_conversion(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->partnerIdStorage->get()->willReturn(42);

        $this->conversionRepository->findOneByOrder($order->reveal())->willReturn(null);
        $this->conversionFactory->createNew()->willReturn(new \stdClass());

        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->expectException(\InvalidArgumentException::class);

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    private function getSubscriber(): CreateConversionSubscriber
    {
        return new CreateConversionSubscriber(
            $this->partnerIdStorage->reveal(),
            $this->conversionFactory->reveal(),
            $this->conversionRepository->reveal(),
        );
    }
}
