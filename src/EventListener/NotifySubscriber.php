<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotifySubscriber implements EventSubscriberInterface
{
    /** @var MessageBusInterface */
    private $messageBus;

    /** @var CookieHandlerInterface */
    private $cookieHandler;

    /** @var OrderTotalCalculatorInterface */
    private $orderTotalCalculator;

    /** @var ProgramContextInterface */
    private $programContext;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        MessageBusInterface $messageBus,
        CookieHandlerInterface $cookieHandler,
        OrderTotalCalculatorInterface $orderTotalCalculator,
        ProgramContextInterface $programContext,
        RequestStack $requestStack
    ) {
        $this->messageBus = $messageBus;
        $this->cookieHandler = $cookieHandler;
        $this->orderTotalCalculator = $orderTotalCalculator;
        $this->programContext = $programContext;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.order.post_complete' => [
                'notify',
            ],
        ];
    }

    public function notify(ResourceControllerEvent $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
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
            (string) $request->getClientIp()
        ));
    }
}
