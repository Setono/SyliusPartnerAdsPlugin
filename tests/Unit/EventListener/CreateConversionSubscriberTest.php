<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\CreateConversionSubscriber;
use Setono\SyliusPartnerAdsPlugin\Model\Conversion;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CreateConversionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CookieHandlerInterface> */
    private ObjectProphecy $cookieHandler;

    /** @var ObjectProphecy<FactoryInterface> */
    private ObjectProphecy $conversionFactory;

    /** @var ObjectProphecy<ConversionRepositoryInterface> */
    private ObjectProphecy $conversionRepository;

    private RequestStack $requestStack;

    private Request $request;

    protected function setUp(): void
    {
        $this->cookieHandler = $this->prophesize(CookieHandlerInterface::class);
        $this->conversionFactory = $this->prophesize(FactoryInterface::class);
        $this->conversionRepository = $this->prophesize(ConversionRepositoryInterface::class);

        $this->request = new Request();
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);
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

        $this->cookieHandler->has($this->request)->willReturn(true);
        $this->cookieHandler->get($this->request)->willReturn(42);

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
        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent(new \stdClass()));
    }

    #[Test]
    public function it_does_nothing_when_there_is_no_request(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $subscriber = new CreateConversionSubscriber(
            new RequestStack(),
            $this->cookieHandler->reveal(),
            $this->conversionFactory->reveal(),
            $this->conversionRepository->reveal(),
        );
        $subscriber->createConversion(new GenericEvent($order->reveal()));
    }

    #[Test]
    public function it_does_nothing_when_the_cookie_is_not_set(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->cookieHandler->has($this->request)->willReturn(false);

        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    #[Test]
    public function it_does_nothing_when_the_cookie_does_not_hold_a_valid_partner_id(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->cookieHandler->has($this->request)->willReturn(true);
        $this->cookieHandler->get($this->request)->willReturn(0);

        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    #[Test]
    public function it_does_nothing_when_the_order_already_has_a_conversion(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->cookieHandler->has($this->request)->willReturn(true);
        $this->cookieHandler->get($this->request)->willReturn(42);

        $this->conversionRepository->findOneByOrder($order->reveal())->willReturn(new Conversion());
        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    #[Test]
    public function it_fails_when_the_factory_does_not_create_a_conversion(): void
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->cookieHandler->has($this->request)->willReturn(true);
        $this->cookieHandler->get($this->request)->willReturn(42);

        $this->conversionRepository->findOneByOrder($order->reveal())->willReturn(null);
        $this->conversionFactory->createNew()->willReturn(new \stdClass());

        $this->conversionRepository->add(Argument::any())->shouldNotBeCalled();

        $this->expectException(\InvalidArgumentException::class);

        $this->getSubscriber()->createConversion(new GenericEvent($order->reveal()));
    }

    private function getSubscriber(): CreateConversionSubscriber
    {
        return new CreateConversionSubscriber(
            $this->requestStack,
            $this->cookieHandler->reveal(),
            $this->conversionFactory->reveal(),
            $this->conversionRepository->reveal(),
        );
    }
}
