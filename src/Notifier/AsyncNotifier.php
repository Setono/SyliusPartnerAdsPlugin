<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Notifier;

use Setono\SyliusPartnerAdsPlugin\Message\Command\CallUrl;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;

final class AsyncNotifier extends Notifier
{
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(Session $session, string $notifyUrl, MessageBusInterface $messageBus)
    {
        parent::__construct($session, $notifyUrl);

        $this->messageBus = $messageBus;
    }

    protected function callUrl(string $url): void
    {
        $this->messageBus->dispatch(new CallUrl($url));
    }
}
