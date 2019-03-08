<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler;

use Setono\SyliusPartnerAdsPlugin\Notifier\AsyncNotifier;
use Setono\SyliusPartnerAdsPlugin\Notifier\SyncNotifier;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterDefaultNotifierPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $enabled = $container->getParameter('setono_sylius_partner_ads.messenger.enabled');

        $container->setAlias('setono_sylius_partner_ads.notifier.default', $enabled ? AsyncNotifier::class : SyncNotifier::class);
    }
}
