<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Doctrine\ORM;

use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderPaymentStates;

class ConversionRepository extends EntityRepository implements ConversionRepositoryInterface
{
    public function findOneByOrder(OrderInterface $order): ?ConversionInterface
    {
        // more than one conversion can exist for an order (see CreateConversionSubscriber), so cap the result
        /** @var ConversionInterface|null $obj */
        $obj = $this->createQueryBuilder('o')
            ->andWhere('o.order = :order')
            ->setParameter('order', $order)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $obj;
    }

    public function hasNotifiedConversionForOrder(OrderInterface $order): bool
    {
        $result = $this->createQueryBuilder('o')
            ->select('o.id')
            ->andWhere('o.order = :order')
            ->andWhere('o.state = :state')
            ->setParameter('order', $order)
            ->setParameter('state', ConversionInterface::STATE_NOTIFIED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $result;
    }

    public function findPending(int $limit, NotifyWhen $notifyWhen): array
    {
        // an order must always have been completed to be eligible - 'paid' is an extra requirement on top of that
        $qb = $this->createQueryBuilder('o')
            ->join('o.order', 'ord')
            ->andWhere('o.state = :state')
            ->andWhere('ord.checkoutState = :checkoutState')
            ->andWhere('ord.state != :cancelledOrderState')
            ->setParameter('state', ConversionInterface::STATE_PENDING)
            ->setParameter('checkoutState', OrderCheckoutStates::STATE_COMPLETED)
            ->setParameter('cancelledOrderState', OrderInterface::STATE_CANCELLED)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults($limit)
        ;

        if (NotifyWhen::Paid === $notifyWhen) {
            $qb
                ->andWhere('ord.paymentState = :paymentState')
                ->setParameter('paymentState', OrderPaymentStates::STATE_PAID)
            ;
        }

        /** @var list<ConversionInterface> $objs */
        $objs = $qb->getQuery()->getResult();

        return $objs;
    }
}
