<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\NotifySubscriber;
use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotifySubscriberTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_dispatches_a_notify_command_on_the_thank_you_page(): void
    {
        $messageBus = $this->prophesize(MessageBusInterface::class);
        $cookieHandler = $this->prophesize(CookieHandlerInterface::class);
        $orderTotalCalculator = $this->prophesize(OrderTotalCalculatorInterface::class);
        $programContext = $this->prophesize(ProgramContextInterface::class);
        $orderRepository = $this->prophesize(OrderRepositoryInterface::class);
        $order = $this->prophesize(OrderInterface::class);
        $program = $this->prophesize(ProgramInterface::class);

        $session = new Session(new MockArraySessionStorage());
        $session->set('sylius_order_id', 123);

        $request = new Request();
        $request->attributes->set('_route', 'sylius_shop_order_thank_you');
        $request->setSession($session);

        $event = new RequestEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $orderRepository->find(123)->willReturn($order->reveal());
        $order->getNumber()->willReturn('000000123');
        $cookieHandler->has($request)->willReturn(true);
        $cookieHandler->get($request)->willReturn(123);
        $programContext->getProgram()->willReturn($program->reveal());
        $program->getProgramId()->willReturn(1234);
        $orderTotalCalculator->get($order->reveal())->willReturn(199.0);

        $messageBus->dispatch(Argument::type(Notify::class))
            ->willReturn(new Envelope(new \stdClass()))
            ->shouldBeCalled();

        $subscriber = new NotifySubscriber(
            $messageBus->reveal(),
            $cookieHandler->reveal(),
            $orderTotalCalculator->reveal(),
            $programContext->reveal(),
            $orderRepository->reveal(),
        );

        $subscriber->notify($event);
    }

    #[Test]
    public function it_does_nothing_when_the_route_is_not_the_thank_you_page(): void
    {
        $messageBus = $this->prophesize(MessageBusInterface::class);
        $cookieHandler = $this->prophesize(CookieHandlerInterface::class);
        $orderTotalCalculator = $this->prophesize(OrderTotalCalculatorInterface::class);
        $programContext = $this->prophesize(ProgramContextInterface::class);
        $orderRepository = $this->prophesize(OrderRepositoryInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'sylius_shop_homepage');

        $event = new RequestEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $messageBus->dispatch(Argument::any())->shouldNotBeCalled();

        $subscriber = new NotifySubscriber(
            $messageBus->reveal(),
            $cookieHandler->reveal(),
            $orderTotalCalculator->reveal(),
            $programContext->reveal(),
            $orderRepository->reveal(),
        );

        $subscriber->notify($event);
    }
}
