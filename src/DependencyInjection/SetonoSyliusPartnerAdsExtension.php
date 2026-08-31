<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
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
         *     notify_when: string,
         *     query_parameter: string,
         *     cookie: array{name: string, expire: int}
         * } $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $container->setParameter('setono_sylius_partner_ads.http_client', $config['http_client']);
        $container->setParameter('setono_sylius_partner_ads.urls.notify', $config['urls']['notify']);
        $container->setParameter('setono_sylius_partner_ads.query_parameter', $config['query_parameter']);
        $container->setParameter('setono_sylius_partner_ads.cookie.name', $config['cookie']['name']);
        $container->setParameter('setono_sylius_partner_ads.cookie.expire', $config['cookie']['expire']);
        $container->setParameter('setono_sylius_partner_ads.notify_when', NotifyWhen::from($config['notify_when']));

        $this->registerResources('setono_sylius_partner_ads', $config['driver'], $config['resources'], $container);

        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependGrid($container);
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
                'setono_sylius_partner_ads_admin_conversion' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => '%setono_sylius_partner_ads.model.conversion.class%',
                        ],
                    ],
                    'sorting' => [
                        'createdAt' => 'desc',
                    ],
                    'fields' => [
                        'order' => [
                            'type' => 'string',
                            'path' => 'order.number',
                            'label' => 'setono_sylius_partner_ads.ui.order',
                        ],
                        'partnerId' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_partner_ads.ui.partner_id',
                        ],
                        'state' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_partner_ads.ui.state',
                        ],
                        'tries' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_partner_ads.ui.tries',
                        ],
                        'lastError' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_partner_ads.ui.last_error',
                        ],
                        'notifiedAt' => [
                            'type' => 'datetime',
                            'label' => 'setono_sylius_partner_ads.ui.notified_at',
                            'sortable' => true,
                        ],
                        'createdAt' => [
                            'type' => 'datetime',
                            'label' => 'setono_sylius_partner_ads.ui.created_at',
                            'sortable' => true,
                        ],
                    ],
                    'filters' => [
                        'state' => [
                            'type' => 'select',
                            'label' => 'setono_sylius_partner_ads.ui.state',
                            'form_options' => [
                                'choices' => [
                                    'setono_sylius_partner_ads.ui.state_pending' => ConversionInterface::STATE_PENDING,
                                    'setono_sylius_partner_ads.ui.state_notified' => ConversionInterface::STATE_NOTIFIED,
                                    'setono_sylius_partner_ads.ui.state_failed' => ConversionInterface::STATE_FAILED,
                                    'setono_sylius_partner_ads.ui.state_skipped' => ConversionInterface::STATE_SKIPPED,
                                ],
                            ],
                        ],
                    ],
                    'actions' => [
                        'item' => [
                            'delete' => ['type' => 'delete'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
