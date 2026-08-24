<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Doctrine\ORM;

use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Webmozart\Assert\Assert;

class ConversionRepository extends EntityRepository implements ConversionRepositoryInterface
{
    public function findOneByOrder(OrderInterface $order): ?ConversionInterface
    {
        $obj = $this->createQueryBuilder('o')
            ->andWhere('o.order = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($obj, ConversionInterface::class);

        return $obj;
    }

    public function findPendingForPaidOrders(int $limit): array
    {
        $objs = $this->createQueryBuilder('o')
            ->join('o.order', 'ord')
            ->andWhere('o.state = :state')
            ->andWhere('ord.paymentState = :paymentState')
            ->setParameter('state', ConversionInterface::STATE_PENDING)
            ->setParameter('paymentState', OrderPaymentStates::STATE_PAID)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($objs);
        Assert::allIsInstanceOf($objs, ConversionInterface::class);

        return array_values($objs);
    }
}
