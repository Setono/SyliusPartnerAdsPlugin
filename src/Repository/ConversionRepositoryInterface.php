<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Repository;

use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConversionRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the first conversion for the given order. More than one can exist - see CreateConversionSubscriber.
     */
    public function findOneByOrder(OrderInterface $order): ?ConversionInterface;

    /**
     * Returns true if a conversion for the given order has already been sent to Partner Ads
     */
    public function hasNotifiedConversionForOrder(OrderInterface $order): bool;

    /**
     * Returns pending conversions whose orders have been completed and - if $notifyWhen is Paid - also paid.
     * Conversions for cancelled orders are never returned.
     *
     * @return list<ConversionInterface>
     */
    public function findPending(int $limit, NotifyWhen $notifyWhen): array;
}
