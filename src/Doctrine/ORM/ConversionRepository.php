<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Doctrine\ORM;

use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
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

    public function findPending(int $limit, NotifyWhen $notifyWhen): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.order', 'ord')
            ->andWhere('o.state = :state')
            ->andWhere('ord.state != :cancelledOrderState')
            ->setParameter('state', ConversionInterface::STATE_PENDING)
            ->setParameter('cancelledOrderState', OrderInterface::STATE_CANCELLED)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults($limit)
        ;

        match ($notifyWhen) {
            NotifyWhen::Completed => $qb
                ->andWhere('ord.checkoutState = :checkoutState')
                ->setParameter('checkoutState', OrderCheckoutStates::STATE_COMPLETED),
            NotifyWhen::Paid => $qb
                ->andWhere('ord.paymentState = :paymentState')
                ->setParameter('paymentState', OrderPaymentStates::STATE_PAID),
        };

        $objs = $qb->getQuery()->getResult();

        Assert::isArray($objs);
        Assert::allIsInstanceOf($objs, ConversionInterface::class);

        return array_values($objs);
    }
}
