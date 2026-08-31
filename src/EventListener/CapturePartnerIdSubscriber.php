<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\Parser\PartnerIdParser;
use Setono\SyliusPartnerAdsPlugin\PartnerIdStorage\PartnerIdStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Captures the partner id that Partner Ads appends to affiliate links (the "paid" query parameter by default) and
 * stores it for the visitor, so that an order completed within the attribution window is credited to the partner.
 */
final readonly class CapturePartnerIdSubscriber implements EventSubscriberInterface
{
    public function __construct(private PartnerIdStorageInterface $partnerIdStorage, private string $queryParameter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['capture'],
        ];
    }

    public function capture(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // only handle 'real' page loads, not AJAX requests like add to cart
        if ($request->isXmlHttpRequest()) {
            return;
        }

        // all() instead of get(): get() throws a BadRequestException (a 400 for the whole page) when the
        // parameter is an array (?paid[]=x), and a malformed affiliate link must never break a shop page
        $partnerId = PartnerIdParser::parse($request->query->all()[$this->queryParameter] ?? null);
        if (null === $partnerId) {
            return;
        }

        // last click wins: a previously stored partner id is overwritten and the attribution window restarts
        $this->partnerIdStorage->store($partnerId);
    }
}
