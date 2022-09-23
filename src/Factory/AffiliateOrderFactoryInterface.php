<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Factory;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface AffiliateOrderFactoryInterface extends FactoryInterface
{
    public function createNew(): AffiliateOrderInterface;

    public function createWithData(
        ProgramInterface $program,
        OrderInterface $order,
        int $partner,
        string $ip
    ): AffiliateOrderInterface;
}
