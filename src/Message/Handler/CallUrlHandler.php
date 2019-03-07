<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Message\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Setono\SyliusPartnerAdsPlugin\Message\Command\CallUrl;
use Webmozart\Assert\Assert;

final class CallUrlHandler
{
    public function __invoke(CallUrl $message)
    {
        $client = new Client([
            RequestOptions::CONNECT_TIMEOUT => 5,
            RequestOptions::TIMEOUT => 15,
        ]);

        $response = $client->request('GET', $message->getUrl());

        Assert::same($response->getStatusCode(), 200);
    }
}
