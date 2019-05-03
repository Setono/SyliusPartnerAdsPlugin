<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Exception;
use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusPartnerAdsExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_partner_ads.http_client', $config['http_client']);
        $container->setParameter('setono_sylius_partner_ads.urls.notify', $config['urls']['notify']);
        $container->setParameter('setono_sylius_partner_ads.query_parameter', $config['query_parameter']);
        $container->setParameter('setono_sylius_partner_ads.cookie.name', $config['cookie']['name']);
        $container->setParameter('setono_sylius_partner_ads.cookie.expire', $config['cookie']['expire']);
        $container->setParameter('setono_sylius_partner_ads.messenger.command_bus', $config['messenger']['command_bus']);
        $container->setParameter('setono_sylius_partner_ads.messenger.transport', $config['messenger']['transport']);

        $this->registerResources('setono_sylius_partner_ads', $config['driver'], $config['resources'], $container);

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $container->getExtensionConfig($this->getAlias()));

        $transport = $config['messenger']['transport'];

        if (null === $transport) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'routing' => [
                    Notify::class => $transport,
                ],
            ],
        ]);
    }
}
