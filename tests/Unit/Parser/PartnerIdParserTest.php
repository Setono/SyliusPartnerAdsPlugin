<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusPartnerAdsPlugin\Parser\PartnerIdParser;

final class PartnerIdParserTest extends TestCase
{
    #[Test]
    #[DataProvider('values')]
    public function it_parses(mixed $value, ?int $expected): void
    {
        self::assertSame($expected, PartnerIdParser::parse($value));
    }

    /**
     * @return iterable<string, array{mixed, int|null}>
     */
    public static function values(): iterable
    {
        yield 'valid' => ['123', 123];
        yield 'smallest valid' => ['1', 1];
        yield 'integer' => [123, 123];
        yield 'zero' => ['0', null];
        yield 'negative' => ['-1', null];
        yield 'garbage' => ['junk', null];
        yield 'empty' => ['', null];
        yield 'decimal' => ['1.5', null];
        yield 'null' => [null, null];
        yield 'array' => [['1'], null];
    }
}
