<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Repository;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface AffiliateOrderRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<array-key, AffiliateOrderInterface>
     */
    public function findNew(): array;
}
