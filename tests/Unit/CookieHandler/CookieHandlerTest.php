<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\CookieHandler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CookieHandlerTest extends TestCase
{
    private string $name = 'name';

    private int $expire = 40;

    private int $partnerId = 1234;

    #[Test]
    public function it_sets(): void
    {
        $response = new Response();
        $this->createCookieHandler($response);

        $cookies = $response->headers->getCookies();

        self::assertCount(1, $cookies);

        $cookie = $cookies[0];

        self::assertSame($this->name, $cookie->getName());
        self::assertSame($this->partnerId, (int) $cookie->getValue());

        $expireInDays = (int) ceil(($cookie->getExpiresTime() - time()) / 60 / 60 / 24);

        self::assertSame($this->expire, $expireInDays);
    }

    #[Test]
    public function it_removes(): void
    {
        $response = new Response();
        $cookieHandler = $this->createCookieHandler($response);

        $cookieHandler->remove($response);

        $cookies = $response->headers->getCookies();

        self::assertCount(1, $cookies);

        $cookie = $cookies[0];

        self::assertSame($this->name, $cookie->getName());
        self::assertNull($cookie->getValue());
    }

    #[Test]
    public function it_gets_the_value(): void
    {
        $cookieHandler = $this->createCookieHandler(new Response());
        $request = $this->createRequest();

        $value = $cookieHandler->get($request);

        self::assertSame($this->partnerId, $value);
    }

    #[Test]
    public function it_returns_true_if_cookie_is_set(): void
    {
        $cookieHandler = $this->createCookieHandler(new Response());
        $request = $this->createRequest();

        self::assertTrue($cookieHandler->has($request));
    }

    #[Test]
    public function it_returns_false_if_cookie_is_not_set(): void
    {
        $cookieHandler = $this->createCookieHandler(new Response());
        $request = $this->createRequest('doesnotexist');

        self::assertFalse($cookieHandler->has($request));
    }

    private function createCookieHandler(Response $response): CookieHandler
    {
        $cookieHandler = new CookieHandler($this->name, $this->expire);
        $cookieHandler->set($response, $this->partnerId);

        return $cookieHandler;
    }

    private function createRequest(?string $name = null): Request
    {
        return new Request([], [], [], [
            $name ?? $this->name => $this->partnerId,
        ]);
    }
}
