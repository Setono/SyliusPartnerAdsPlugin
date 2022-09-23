<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use DateTimeImmutable;
use Setono\MainRequestTrait\MainRequestTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final class CookieHandler implements CookieHandlerInterface, EventSubscriberInterface
{
    use MainRequestTrait;

    private RequestStack $requestStack;

    private string $queryParameter;

    private string $cookieName;

    private int $expire;

    public function __construct(RequestStack $requestStack, string $queryParameter, string $cookieName, int $expire)
    {
        $this->requestStack = $requestStack;
        $this->queryParameter = $queryParameter;
        $this->cookieName = $cookieName;
        $this->expire = $expire;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'checkCookie',
        ];
    }

    public function checkCookie(ResponseEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        $partnerId = $request->query->get($this->queryParameter);
        if (!is_numeric($partnerId)) {
            return;
        }

        $this->set($event->getResponse(), (int) $partnerId);
    }

    public function set(Response $response, int $partnerId): void
    {
        $response->headers->setCookie(new Cookie(
            $this->cookieName,
            (string) $partnerId,
            new DateTimeImmutable(sprintf('+%d days', $this->expire))
        ));
    }

    public function value(Request $request = null): int
    {
        if (null === $request) {
            $request = $this->getMainRequestFromRequestStack($this->requestStack);
            Assert::notNull($request);
        }

        return (int) $request->cookies->get($this->cookieName);
    }

    public function isset(Request $request = null): bool
    {
        if (null === $request) {
            $request = $this->getMainRequestFromRequestStack($this->requestStack);
            Assert::notNull($request);
        }

        return $request->cookies->has($this->cookieName);
    }
}
