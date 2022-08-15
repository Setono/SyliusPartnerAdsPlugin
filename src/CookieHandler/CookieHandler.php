<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CookieHandler implements CookieHandlerInterface
{
    private string $cookieName;

    private int $expire;

    public function __construct(string $cookieName, int $expire)
    {
        $this->cookieName = $cookieName;
        $this->expire = $expire;
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
        return (int) $request->cookies->get($this->cookieName);
    }

    public function has(Request $request): bool
    {
        return $request->cookies->has($this->cookieName);
    }
}
