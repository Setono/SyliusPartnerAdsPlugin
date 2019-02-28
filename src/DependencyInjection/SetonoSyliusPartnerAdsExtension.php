<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusPartnerAdsExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_partner_ads.program_id', $config['program_id']);
        $container->setParameter('setono_sylius_partner_ads.query_parameter', $config['query_parameter']);
        $container->setParameter('setono_sylius_partner_ads.urls.notify', $config['urls']['notify']);
        $container->setParameter('setono_sylius_partner_ads.cookie.name', $config['cookie']['name']);
        $container->setParameter('setono_sylius_partner_ads.cookie.expire', $config['cookie']['expire']);

        $loader->load('services.xml');
    }
}
