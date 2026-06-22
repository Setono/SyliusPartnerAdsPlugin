<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class NotifySubscriber implements EventSubscriberInterface
{
    public function __construct(private MessageBusInterface $messageBus, private CookieHandlerInterface $cookieHandler, private OrderTotalCalculatorInterface $orderTotalCalculator, private ProgramContextInterface $programContext, private OrderRepositoryInterface $orderRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'notify',
        ];
    }

    public function notify(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$request->attributes->has('_route')) {
            return;
        }

        $route = $request->attributes->get('_route');
        if ('sylius_shop_order_thank_you' !== $route) {
            return;
        }

        /** @var int|null $orderId */
        $orderId = $request->getSession()->get('sylius_order_id');

        if (null === $orderId) {
            return;
        }

        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->find($orderId);
        if (null === $order) {
            return;
        }

        if (!$this->cookieHandler->has($request)) {
            return;
        }

        $program = $this->programContext->getProgram();

        if (null === $program || $program->getProgramId() === null) {
            return;
        }

        $this->messageBus->dispatch(new Notify(
            $program->getProgramId(),
            (string) $order->getNumber(),
            $this->orderTotalCalculator->get($order),
            $this->cookieHandler->get($request),
            (string) $request->getClientIp(),
        ));
    }
}
