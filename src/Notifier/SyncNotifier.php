<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Notifier;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class SyncNotifier extends Notifier
{
    protected function callUrl(string $url): void
    {
        $client = new Client([
            RequestOptions::CONNECT_TIMEOUT => 5,
            RequestOptions::TIMEOUT => 15,
        ]);

        $client->request('GET', $url);
    }
}
