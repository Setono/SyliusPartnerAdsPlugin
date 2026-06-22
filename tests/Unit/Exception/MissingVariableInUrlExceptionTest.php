<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;

final class MissingVariableInUrlExceptionTest extends TestCase
{
    #[Test]
    public function it_exposes_the_url_and_missing_variable(): void
    {
        $exception = new MissingVariableInUrlException('https://example.com', '{program_id}');

        self::assertSame('https://example.com', $exception->getUrl());
        self::assertSame('{program_id}', $exception->getMissingVariable());
        self::assertSame('The URL https://example.com is missing variable {program_id}', $exception->getMessage());
    }
}
