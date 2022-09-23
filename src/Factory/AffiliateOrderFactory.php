<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Factory;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class AffiliateOrderFactory implements AffiliateOrderFactoryInterface
{
    private FactoryInterface $decorated;

    public function __construct(FactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function createNew(): AffiliateOrderInterface
    {
        /** @var AffiliateOrderInterface|object $obj */
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, AffiliateOrderInterface::class);

        return $obj;
    }

    public function createWithData(
        ProgramInterface $program,
        OrderInterface $order,
        int $partner,
        string $ip
    ): AffiliateOrderInterface {
        $obj = $this->createNew();
        $obj->setProgram($program);
        $obj->setOrder($order);
        $obj->setPartner($partner);
        $obj->setIp($ip);

        return $obj;
    }
}
