<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\Exception\InterfaceNotFoundException;

final class InterfaceNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function it_exposes_the_interface(): void
    {
        $exception = new InterfaceNotFoundException('Some\\Interface');

        self::assertSame('Some\\Interface', $exception->getInterface());
        self::assertSame('The interface "Some\\Interface" was not found', $exception->getMessage());
    }
}
