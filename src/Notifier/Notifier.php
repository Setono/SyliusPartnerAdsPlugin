<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Notifier;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class Notifier implements NotifierInterface
{
    /**
     * @var int
     */
    private $programId;

    /**
     * @var string
     */
    private $notifyUrl;

    public function __construct(int $programId, string $notifyUrl)
    {
        $this->programId = $programId;
        $this->notifyUrl = $notifyUrl;
    }

    public function notify(string $orderId, float $orderTotal, string $partnerId, string $ip): void
    {
        $url = str_replace([
            '$program_id', '$partner_id', '$ip', '$order_id', '$order_total'
        ], [
            $this->programId, $partnerId, $ip, $orderId, $orderTotal
        ], $this->notifyUrl);

        // todo inject a client to be able to test this and use an HTTP abstraction instead like php-http

        $client = new Client([
            RequestOptions::CONNECT_TIMEOUT => 5,
            RequestOptions::TIMEOUT => 15
        ]);

        $client->request('GET', $url);
    }
}
