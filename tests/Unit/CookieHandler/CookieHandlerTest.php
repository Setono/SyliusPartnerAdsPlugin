<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\CookieHandler;

use PHPUnit\Framework\Attributes\DataProvider;
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
    public function it_throws_when_getting_a_value_that_is_not_set(): void
    {
        $cookieHandler = new CookieHandler($this->name, $this->expire);

        $this->expectException(\InvalidArgumentException::class);

        $cookieHandler->get(new Request());
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

    #[Test]
    #[DataProvider('invalidCookieValues')]
    public function it_treats_a_cookie_without_a_valid_partner_id_as_absent(mixed $value): void
    {
        $cookieHandler = new CookieHandler($this->name, $this->expire);
        $request = new Request([], [], [], [$this->name => $value]);

        self::assertFalse($cookieHandler->has($request));

        $this->expectException(\InvalidArgumentException::class);

        $cookieHandler->get($request);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidCookieValues(): iterable
    {
        yield 'empty' => [''];
        yield 'garbage' => ['junk'];
        yield 'zero' => ['0'];
        yield 'negative' => ['-1'];
        yield 'array' => [['1']];
    }

    private function createCookieHandler(Response $response): CookieHandler
    {
        $cookieHandler = new CookieHandler($this->name, $this->expire);
        $cookieHandler->set($response, $this->partnerId);

        return $cookieHandler;
    }

    private function createRequest(?string $name = null): Request
    {
        // Cookies arrive as strings over HTTP, so the value is a string here on purpose. This also
        // verifies that CookieHandler::get() parses the string into an integer.
        return new Request([], [], [], [
            $name ?? $this->name => (string) $this->partnerId,
        ]);
    }
}
