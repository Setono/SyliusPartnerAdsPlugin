<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\EventListener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\NotifySubscriber;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use stdClass;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class NotifySubscriberSpec extends ObjectBehavior
{
    public function let(
        MessageBusInterface $messageBus,
        CookieHandlerInterface $cookieHandler,
        OrderTotalCalculatorInterface $orderTotalCalculator,
        ProgramContextInterface $programContext,
        RequestStack $requestStack
    ): void {
        $this->beConstructedWith($messageBus, $cookieHandler, $orderTotalCalculator, $programContext, $requestStack);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NotifySubscriber::class);
    }

    public function it_notifies(
        ResourceControllerEvent $event,
        OrderInterface $order,
        RequestStack $requestStack,
        CookieHandlerInterface $cookieHandler,
        OrderTotalCalculatorInterface $orderTotalCalculator,
        ProgramContextInterface $programContext,
        ProgramInterface $program,
        MessageBusInterface $messageBus,
        Request $request,
        StampInterface $stamp
    ): void {
        $event->getSubject()->willReturn($order);
        $requestStack->getMasterRequest()->willReturn($request);
        $cookieHandler->has($request)->willReturn(true);
        $cookieHandler->get($request)->willReturn(123);
        $programContext->getProgram()->willReturn($program);
        $program->getProgramId()->willReturn(1234);
        $orderTotalCalculator->get($order)->willReturn(199.0);
        $envelope = new Envelope(new stdClass(), $stamp->getWrappedObject());

        $messageBus->dispatch(Argument::any())->willReturn($envelope)->shouldBeCalled();

        $this->notify($event);
    }
}
