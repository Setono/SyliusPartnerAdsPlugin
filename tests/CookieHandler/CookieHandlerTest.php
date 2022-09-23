<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\CookieHandler;

use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CookieHandlerTest extends TestCase
{
    private $name = 'name';

    private $expire = 40;

    private $partnerId = 1234;

    /**
     * @test
     */
    public function it_sets(): void
    {
        $response = new Response();
        $this->createCookieHandler($response);

        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];

        $this->assertSame($this->name, $cookie->getName());
        $this->assertSame($this->partnerId, (int) $cookie->getValue());

        $expireInDays = (int) ceil(($cookie->getExpiresTime() - time()) / 60 / 60 / 24);

        $this->assertSame($this->expire, $expireInDays);
    }

    /**
     * @test
     */
    public function it_gets_the_value(): void
    {
        $cookieHandler = $this->createCookieHandler(new Response());
        $request = $this->createRequest();

        $value = $cookieHandler->value($request);

        $this->assertSame($this->partnerId, $value);
    }

    /**
     * @test
     */
    public function it_returns_true_if_cookie_is_set(): void
    {
        $cookieHandler = $this->createCookieHandler(new Response());
        $request = $this->createRequest();

        $this->assertTrue($cookieHandler->isset($request));
    }

    /**
     * @test
     */
    public function it_returns_false_if_cookie_is_not_set(): void
    {
        $cookieHandler = $this->createCookieHandler(new Response());
        $request = $this->createRequest('doesnotexist');

        $this->assertFalse($cookieHandler->isset($request));
    }

    private function createCookieHandler(Response $response): CookieHandler
    {
        $cookieHandler = new CookieHandler($this->name, $this->expire);
        $cookieHandler->set($response, $this->partnerId);

        return $cookieHandler;
    }

    private function createRequest(string $name = null): Request
    {
        return new Request([], [], [], [
            $name ?? $this->name => $this->partnerId,
        ]);
    }
}
