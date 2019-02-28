<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\Notifier\NotifierInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class NotifySubscriber implements EventSubscriberInterface
{
    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @var CookieHandlerInterface
     */
    private $cookieHandler;

    /**
     * @var bool
     */
    private $notify = false;

    /**
     * @var string
     */
    private $orderId;

    /**
     * @var float
     */
    private $orderTotal;

    public function __construct(NotifierInterface $notifier, CookieHandlerInterface $cookieHandler)
    {
        $this->notifier = $notifier;
        $this->cookieHandler = $cookieHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.order.post_complete' => [
                'shouldNotify',
            ],
            KernelEvents::TERMINATE => [
                'notify',
            ],
        ];
    }

    public function shouldNotify(ResourceControllerEvent $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $this->orderId = (string) $order->getNumber();
        $this->orderTotal = $order->getTotal();

        $this->notify = true;
    }

    public function notify(PostResponseEvent $event): void
    {
        if (!$this->notify) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        $statusCode = $event->getResponse()->getStatusCode();

        if ($statusCode < 200 || $statusCode > 299) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->cookieHandler->has($request)) {
            return;
        }

        $this->notifier->notify($this->orderId, $this->orderTotal, $this->cookieHandler->get($request), (string) $request->getClientIp());
    }
}
