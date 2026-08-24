<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Repository;

use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConversionRepositoryInterface extends RepositoryInterface
{
    public function findOneByOrder(OrderInterface $order): ?ConversionInterface;

    /**
     * Returns pending conversions whose order has been fully paid
     *
     * @return list<ConversionInterface>
     */
    public function findPendingForPaidOrders(int $limit): array;
}
