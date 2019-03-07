<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusPartnerAdsExtension extends Extension implements PrependExtensionInterface
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
        $container->setParameter('setono_sylius_partner_ads.messenger.enabled', $config['messenger']['enabled']);
        $container->setParameter('setono_sylius_partner_ads.messenger.command_bus', $config['messenger']['command_bus'] ?? null);

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // see https://github.com/kalessil/phpinspectionsea/blob/master/docs/performance.md#slow-array-function-used-in-loop
        $definedTransports = [[]];

        $frameworkConfigs = $container->getExtensionConfig('framework');
        foreach ($frameworkConfigs as $frameworkConfig) {
            foreach ($frameworkConfig as $component => $componentConfig) {
                if ('messenger' !== $component || !array_key_exists('transports', $componentConfig) || !is_array($componentConfig['transports'])) {
                    continue;
                }

                $definedTransports[] = $componentConfig['transports'];
            }
        }

        $definedTransports = array_merge(...$definedTransports);

        if (!array_key_exists('amqp', $definedTransports)) {
            throw new \InvalidArgumentException('This plugin only works if you have a \'amqp\' transport defined, else do not disable the messenger component by setting setono_sylius_partner_ads.messenger.enabled to false'); // @todo better exception
        }

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'routing' => [
                    '*' => 'amqp',
                ],
            ],
        ]);
    }
}
