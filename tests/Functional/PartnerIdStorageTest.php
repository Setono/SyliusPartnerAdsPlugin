<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\PartnerIdStorage\PartnerIdStorageInterface;
use Setono\SyliusPartnerAdsPlugin\Tests\Application\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Proves that the client bundle is registered and the storage is wired to it. Without a request the bundle hands
 * out an in-memory client, so this needs neither a database nor an HTTP request.
 */
final class PartnerIdStorageTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // see AdminRoutingTest
        restore_exception_handler();
    }

    #[Test]
    public function it_stores_and_returns_the_partner_id_through_the_client_metadata(): void
    {
        self::bootKernel();

        $storage = self::getContainer()->get(PartnerIdStorageInterface::class);
        self::assertInstanceOf(PartnerIdStorageInterface::class, $storage);

        self::assertNull($storage->get());

        $storage->store(123);

        self::assertSame(123, $storage->get());
    }
}
