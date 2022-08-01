<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Doctrine\ORM;

use Doctrine\DBAL\Types\Types;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class ProgramRepository extends EntityRepository implements ProgramRepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?ProgramInterface
    {
        $obj = $this->createQueryBuilder('o')
            ->andWhere('o.channel = :channel')
            ->andWhere('o.enabled = true')
            ->setParameter('channel', $channel, Types::OBJECT)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($obj, ProgramInterface::class);

        return $obj;
    }
}
