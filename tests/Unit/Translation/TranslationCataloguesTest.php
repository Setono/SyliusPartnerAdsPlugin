<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every shipped locale must carry exactly the keys of the English catalogue, see CLAUDE.md
 */
final class TranslationCataloguesTest extends TestCase
{
    private const TRANSLATIONS_DIR = __DIR__ . '/../../../translations';

    private const LOCALES = ['en', 'da', 'sv', 'no', 'fi', 'de', 'fr', 'es', 'it', 'nl', 'pl', 'pt', 'cs', 'hu', 'ro', 'uk'];

    #[Test]
    #[DataProvider('catalogues')]
    public function it_has_the_same_keys_as_the_english_catalogue(string $domain, string $locale): void
    {
        $expected = self::keys($domain, 'en');
        $actual = self::keys($domain, $locale);

        self::assertSame([], array_values(array_diff($expected, $actual)), sprintf('Keys missing from %s.%s', $domain, $locale));
        self::assertSame([], array_values(array_diff($actual, $expected)), sprintf('Unexpected keys in %s.%s', $domain, $locale));
    }

    #[Test]
    #[DataProvider('catalogues')]
    public function it_has_no_empty_translations(string $domain, string $locale): void
    {
        foreach (self::flatten(self::parse($domain, $locale)) as $key => $value) {
            self::assertIsString($value, sprintf('%s.%s: %s', $domain, $locale, $key));
            self::assertNotSame('', trim($value), sprintf('%s.%s: %s is empty', $domain, $locale, $key));
        }
    }

    #[Test]
    public function it_ships_exactly_the_documented_locales(): void
    {
        foreach (['messages', 'validators'] as $domain) {
            $files = glob(sprintf('%s/%s.*.yaml', self::TRANSLATIONS_DIR, $domain));
            self::assertNotFalse($files);

            $locales = array_map(static fn (string $file): string => explode('.', basename($file))[1], $files);
            sort($locales);

            $expected = self::LOCALES;
            sort($expected);

            self::assertSame($expected, $locales, sprintf('The %s catalogues do not match the documented locales', $domain));
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function catalogues(): iterable
    {
        foreach (['messages', 'validators'] as $domain) {
            foreach (self::LOCALES as $locale) {
                yield sprintf('%s.%s', $domain, $locale) => [$domain, $locale];
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function keys(string $domain, string $locale): array
    {
        return array_keys(self::flatten(self::parse($domain, $locale)));
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function parse(string $domain, string $locale): array
    {
        $parsed = Yaml::parseFile(sprintf('%s/%s.%s.yaml', self::TRANSLATIONS_DIR, $domain, $locale));
        self::assertIsArray($parsed);

        return $parsed;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = '' === $prefix ? (string) $key : sprintf('%s.%s', $prefix, $key);
            if (is_array($value)) {
                $flat += self::flatten($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }
}
