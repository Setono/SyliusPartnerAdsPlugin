<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class SetCookieSubscriber implements EventSubscriberInterface
{
    public function __construct(private CookieHandlerInterface $cookieHandler, private string $queryParameter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                'setCookie',
            ],
        ];
    }

    public function setCookie(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only add handle 'real' page loads, not AJAX requests like add to cart
        if ($request->isXmlHttpRequest()) {
            return;
        }

        if (!$request->query->has($this->queryParameter)) {
            return;
        }

        $this->cookieHandler->set($event->getResponse(), (int) $request->query->get($this->queryParameter));
    }
}
