<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler;

use Setono\SyliusPartnerAdsPlugin\Exception\NotExactlyOneBusDefinedException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterCommandBusPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $enabled = $container->getParameter('setono_sylius_partner_ads.messenger.enabled');
        if (!$enabled) {
            return;
        }

        $bus = $container->getParameter('setono_sylius_partner_ads.messenger.command_bus');
        if (null === $bus) {
            $buses = $container->findTaggedServiceIds('messenger.bus');

            if (count($buses) !== 1) {
                throw new NotExactlyOneBusDefinedException();
            }

            $bus = array_keys($buses)[0];
        }

        $container->setAlias('setono_sylius_partner_ads.messenger.command_bus', $bus);
    }
}
