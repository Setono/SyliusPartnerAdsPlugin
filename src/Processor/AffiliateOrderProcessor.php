<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Processor;

use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Setono\SyliusPartnerAdsPlugin\ValueObject\NotifyUrl;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

final class AffiliateOrderProcessor implements AffiliateOrderProcessorInterface
{
    private HttpClientInterface $httpClient;

    private OrderTotalCalculatorInterface $orderTotalCalculator;

    public function __construct(HttpClientInterface $httpClient, OrderTotalCalculatorInterface $orderTotalCalculator)
    {
        $this->httpClient = $httpClient;
        $this->orderTotalCalculator = $orderTotalCalculator;
    }

    public function process(AffiliateOrderInterface $affiliateOrder): void
    {
        $program = $affiliateOrder->getProgram();
        Assert::notNull($program);

        $order = $affiliateOrder->getOrder();
        Assert::notNull($order);

        $url = new NotifyUrl(
            (int) $program->getProgramId(),
            (int) $affiliateOrder->getPartner(),
            (string) $affiliateOrder->getIp(),
            (string) $order->getNumber(),
            $this->orderTotalCalculator->calculate($order)
        );

        Assert::same($this->httpClient->request('GET', (string) $url)->getStatusCode(), 200);
    }
}
