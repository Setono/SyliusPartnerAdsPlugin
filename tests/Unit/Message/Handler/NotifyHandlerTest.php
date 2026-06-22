<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Message\Handler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;
use Setono\SyliusPartnerAdsPlugin\Message\Handler\NotifyHandler;

final class NotifyHandlerTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_notifies_the_client_with_the_message_data(): void
    {
        $client = $this->prophesize(ClientInterface::class);
        $client->notify(123, 'order-123', 199.95, 456, '127.0.0.1')->shouldBeCalled();

        $handler = new NotifyHandler($client->reveal());
        $handler(new Notify(123, 'order-123', 199.95, 456, '127.0.0.1'));
    }
}
