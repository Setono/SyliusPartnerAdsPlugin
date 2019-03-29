<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler;

use Setono\SyliusPartnerAdsPlugin\Notifier\AsyncNotifier;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterAsyncNotifierPass implements CompilerPassInterface
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

        $definition = new Definition(AsyncNotifier::class, [
            $container->getParameter('setono_sylius_partner_ads.urls.notify'),
            new Reference('setono_sylius_partner_ads.messenger.command_bus'),
        ]);

        $container->setDefinition(AsyncNotifier::class, $definition);
    }
}
