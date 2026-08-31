<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\PartnerIdStorage;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Client\Client;
use Setono\Client\Metadata;
use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\SyliusPartnerAdsPlugin\PartnerIdStorage\ClientMetadataPartnerIdStorage;

final class ClientMetadataPartnerIdStorageTest extends TestCase
{
    use ProphecyTrait;

    private const KEY = ClientMetadataPartnerIdStorage::METADATA_KEY;

    private const ATTRIBUTION_WINDOW = 40;

    private const TTL = self::ATTRIBUTION_WINDOW * 86_400;

    #[Test]
    public function it_stores_the_partner_id_with_the_attribution_window_as_ttl(): void
    {
        $metadata = new Metadata();
        $storage = $this->createStorage($metadata);

        $before = time();
        $storage->store(123);
        $after = time();

        self::assertSame(123, $storage->get());
        self::assertSame(123, $metadata->get(self::KEY));

        $expiresAt = self::expiresAt($metadata);
        self::assertGreaterThanOrEqual($before + self::TTL, $expiresAt);
        self::assertLessThanOrEqual($after + self::TTL, $expiresAt);
    }

    #[Test]
    public function it_overwrites_a_previously_stored_partner_id_and_restarts_the_window(): void
    {
        $metadata = new Metadata([self::KEY => 1, Metadata::EXPIRES_KEY => [self::KEY => time() + 10]]);
        $storage = $this->createStorage($metadata);

        $before = time();
        $storage->store(2);

        self::assertSame(2, $storage->get());
        self::assertGreaterThanOrEqual($before + self::TTL, self::expiresAt($metadata));
    }

    #[Test]
    public function it_returns_null_when_nothing_is_stored(): void
    {
        self::assertNull($this->createStorage(new Metadata())->get());
    }

    #[Test]
    #[DataProvider('storedPartnerIds')]
    public function it_returns_the_stored_partner_id(mixed $stored, int $expected): void
    {
        self::assertSame($expected, $this->createStorage(new Metadata([self::KEY => $stored]))->get());
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function storedPartnerIds(): iterable
    {
        yield 'integer' => [123, 123];
        yield 'numeric string' => ['123', 123];
    }

    #[Test]
    public function it_returns_null_when_the_stored_partner_id_has_expired(): void
    {
        $metadata = new Metadata([self::KEY => 123, Metadata::EXPIRES_KEY => [self::KEY => time() - 1]]);

        self::assertNull($this->createStorage($metadata)->get());
    }

    #[Test]
    #[DataProvider('invalidStoredValues')]
    public function it_treats_an_invalid_stored_value_as_absent_without_removing_it(mixed $value): void
    {
        $metadata = new Metadata([self::KEY => $value]);

        self::assertNull($this->createStorage($metadata)->get());

        // a write here would make the client bundle flush during checkout, see ClientMetadataPartnerIdStorage::get()
        self::assertTrue($metadata->has(self::KEY));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidStoredValues(): iterable
    {
        yield 'empty' => [''];
        yield 'garbage' => ['junk'];
        yield 'zero string' => ['0'];
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'decimal' => ['1.5'];
        yield 'array' => [['1']];
        yield 'null' => [null];
    }

    private function createStorage(Metadata $metadata): ClientMetadataPartnerIdStorage
    {
        $clientContext = $this->prophesize(ClientContextInterface::class);
        $clientContext->getClient()->willReturn(new Client('client-id', $metadata));

        return new ClientMetadataPartnerIdStorage($clientContext->reveal(), self::ATTRIBUTION_WINDOW);
    }

    private static function expiresAt(Metadata $metadata): int
    {
        $expires = $metadata->toArray()[Metadata::EXPIRES_KEY] ?? null;
        self::assertIsArray($expires);
        self::assertIsInt($expires[self::KEY] ?? null);

        return $expires[self::KEY];
    }
}
