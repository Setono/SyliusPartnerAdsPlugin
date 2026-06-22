<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Setono\SyliusPartnerAdsPlugin\Exception\RequestFailedException;

final class RequestFailedExceptionTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_exposes_the_request_response_and_status_code(): void
    {
        $request = $this->prophesize(RequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $exception = new RequestFailedException($request, $response, 500);

        self::assertSame($request, $exception->getRequest());
        self::assertSame($response, $exception->getResponse());
        self::assertSame(500, $exception->getStatusCode());
        self::assertSame('Request failed with status code 500', $exception->getMessage());
    }
}
