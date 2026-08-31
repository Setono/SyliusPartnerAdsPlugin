<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use Setono\SyliusPartnerAdsPlugin\Parser\PartnerIdParser;
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
        $partnerId = $this->parse($request);

        // Fail loudly on misuse: callers must check has() first. Without this guard a missing or
        // tampered cookie would silently become partner id 0 and be reported to Partner Ads.
        Assert::notNull($partnerId, sprintf('No "%s" cookie holding a valid partner id found on the request', $this->cookieName));

        return $partnerId;
    }

    public function has(Request $request): bool
    {
        return null !== $this->parse($request);
    }

    private function parse(Request $request): ?int
    {
        // all() instead of get(): get() throws when the cookie is an array, see PartnerIdParser
        return PartnerIdParser::parse($request->cookies->all()[$this->cookieName] ?? null);
    }
}
