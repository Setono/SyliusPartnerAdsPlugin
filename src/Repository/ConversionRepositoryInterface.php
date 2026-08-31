<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Repository;

use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\NotifyWhen;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConversionRepositoryInterface extends RepositoryInterface
{
    public function findOneByOrder(OrderInterface $order): ?ConversionInterface;

    /**
     * Returns pending conversions whose orders are eligible to be sent to Partner Ads according to $notifyWhen.
     * Conversions for cancelled orders are never returned.
     *
     * @return list<ConversionInterface>
     */
    public function findPending(int $limit, NotifyWhen $notifyWhen): array;
}
