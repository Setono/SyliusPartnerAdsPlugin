<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Message\Handler;

use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;

final readonly class NotifyHandler
{
    public function __construct(private ClientInterface $client)
    {
    }

    public function __invoke(Notify $message): void
    {
        $this->client->notify($message->getProgramId(), $message->getOrderId(), $message->getOrderTotal(), $message->getPartnerId(), $message->getIp());
    }
}
