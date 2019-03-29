<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Notifier;

use Symfony\Component\HttpFoundation\Session\Session;

abstract class Notifier implements NotifierInterface
{
    /**
     * @var string
     */
    private $notifyUrl;

    /**
     * @var Session
     */
    private $session;

    public function __construct(Session $session, string $notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
        $this->session = $session;
    }

    public function notify(int $programId, string $orderId, string $orderTotal, string $partnerId, string $ip): void
    {
        $url = str_replace([
            '$program_id', '$partner_id', '$ip', '$order_id', '$order_total',
        ], [
            $programId, $partnerId, $ip, $orderId, $orderTotal,
        ], $this->notifyUrl);

        $this->callUrl($url);

        $this->session->set('partner_ads_notified', true);
        $this->session->save();
    }

    abstract protected function callUrl(string $url): void;

    public function hasBeenNotified(): bool
    {
        return $this->session->has('partner_ads_notified');
    }
}
