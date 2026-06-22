<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final readonly class CookieHandler implements CookieHandlerInterface
{
    public function __construct(private string $cookieName, private int $expire)
    {
    }

    public function set(Response $response, int $partnerId): void
    {
        $cookie = new Cookie($this->cookieName, (string) $partnerId, sprintf('now + %s days', $this->expire));
        $response->headers->setCookie($cookie);
    }

    public function remove(Response $response): void
    {
        $response->headers->clearCookie($this->cookieName);
    }

    public function get(Request $request): int
    {
        $value = $request->cookies->get($this->cookieName);

        // Fail loudly on misuse: callers must check has() first. Without this guard a missing
        // cookie would silently be cast to partner id 0 and reported to Partner Ads.
        Assert::notNull($value, sprintf('No "%s" cookie found on the request', $this->cookieName));

        return (int) $value;
    }

    public function has(Request $request): bool
    {
        return $request->cookies->has($this->cookieName);
    }
}
