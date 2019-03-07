<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Notifier;

abstract class Notifier implements NotifierInterface
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

    public function notify(string $orderId, string $orderTotal, string $partnerId, string $ip): void
    {
        $url = str_replace([
            '$program_id', '$partner_id', '$ip', '$order_id', '$order_total',
        ], [
            $this->programId, $partnerId, $ip, $orderId, $orderTotal,
        ], $this->notifyUrl);

        $this->callUrl($url);
    }

    abstract protected function callUrl(string $url): void;
}
