<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\Model\Conversion;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;

final class ConversionTest extends TestCase
{
    #[Test]
    public function it_has_sensible_defaults(): void
    {
        $conversion = new Conversion();

        self::assertNull($conversion->getId());
        self::assertNull($conversion->getOrder());
        self::assertNull($conversion->getPartnerId());
        self::assertSame(ConversionInterface::STATE_PENDING, $conversion->getState());
        self::assertSame(0, $conversion->getTries());
        self::assertNull($conversion->getLastError());
        self::assertNull($conversion->getNotifiedAt());
        self::assertEqualsWithDelta(time(), $conversion->getCreatedAt()->getTimestamp(), 5);
    }

    #[Test]
    public function it_increments_tries(): void
    {
        $conversion = new Conversion();

        $conversion->incrementTries();
        $conversion->incrementTries();

        self::assertSame(2, $conversion->getTries());
    }
}
