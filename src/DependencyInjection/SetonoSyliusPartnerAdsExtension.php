<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Setono\SyliusPartnerAdsPlugin\Message\Command\Notify;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class SetonoSyliusPartnerAdsExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     driver: string,
         *     resources: array<string, mixed>,
         *     http_client: string|null,
         *     urls: array{notify: string},
         *     query_parameter: string,
         *     cookie: array{name: string, expire: int},
         *     messenger: array{command_bus: string, transport: string|null}
         * } $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $container->setParameter('setono_sylius_partner_ads.http_client', $config['http_client']);
        $container->setParameter('setono_sylius_partner_ads.urls.notify', $config['urls']['notify']);
        $container->setParameter('setono_sylius_partner_ads.query_parameter', $config['query_parameter']);
        $container->setParameter('setono_sylius_partner_ads.cookie.name', $config['cookie']['name']);
        $container->setParameter('setono_sylius_partner_ads.cookie.expire', $config['cookie']['expire']);
        $container->setParameter('setono_sylius_partner_ads.messenger.command_bus', $config['messenger']['command_bus']);
        $container->setParameter('setono_sylius_partner_ads.messenger.transport', $config['messenger']['transport']);

        $this->registerResources('setono_sylius_partner_ads', $config['driver'], $config['resources'], $container);

        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        /** @var array{resources: array{program: array{classes: array{model: string}}}, messenger: array{transport: string|null}} $config */
        $config = $this->processConfiguration(
            $this->getConfiguration([], $container),
            $container->getExtensionConfig($this->getAlias()),
        );

        // The model class parameter is normally set by registerResources() during load(), but the
        // sylius_grid extension resolves it while loading its own configuration. Depending on bundle
        // registration order that can happen before this plugin's load() runs, so we set it here in
        // prepend() (which always runs before any load()) to make the grid registration order-independent.
        $container->setParameter('setono_sylius_partner_ads.model.program.class', $config['resources']['program']['classes']['model']);

        $this->prependGrid($container);
        $this->prependMessenger($container, $config['messenger']['transport']);
    }

    private function prependGrid(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_partner_ads_admin_program' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => '%setono_sylius_partner_ads.model.program.class%',
                        ],
                    ],
                    'fields' => [
                        'programId' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_partner_ads.ui.program_id',
                        ],
                        'channel' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_partner_ads.ui.channel',
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => ['type' => 'create'],
                        ],
                        'item' => [
                            'update' => ['type' => 'update'],
                            'delete' => ['type' => 'delete'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function prependMessenger(ContainerBuilder $container, ?string $transport): void
    {
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
