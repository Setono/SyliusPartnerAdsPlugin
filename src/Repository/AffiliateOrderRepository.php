<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Repository;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class AffiliateOrderRepository extends EntityRepository implements AffiliateOrderRepositoryInterface
{
    public function findNew(): array
    {
        $objs = $this->createQueryBuilder('o')
            ->andWhere('o.state = :state')
            ->setParameter('state', AffiliateOrderInterface::STATE_INITIAL)
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($objs);
        Assert::allIsInstanceOf($objs, AffiliateOrderInterface::class);

        return $objs;
    }
}
