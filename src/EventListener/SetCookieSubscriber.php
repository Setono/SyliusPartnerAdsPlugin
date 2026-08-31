<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\Parser\PartnerIdParser;
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

        // all() instead of get(): get() throws a BadRequestException (a 400 for the whole page) when the
        // parameter is an array (?paid[]=x), and a malformed affiliate link must never break a shop page
        $partnerId = PartnerIdParser::parse($request->query->all()[$this->queryParameter] ?? null);
        if (null === $partnerId) {
            return;
        }

        $this->cookieHandler->set($event->getResponse(), $partnerId);
    }
}
