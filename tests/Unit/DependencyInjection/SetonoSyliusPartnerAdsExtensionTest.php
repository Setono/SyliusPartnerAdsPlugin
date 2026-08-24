<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculator;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\SetonoSyliusPartnerAdsExtension;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusPartnerAdsExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new SetonoSyliusPartnerAdsExtension()];
    }

    #[Test]
    public function after_loading_the_parameters_and_services_have_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.http_client');
        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.urls.notify');
        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.query_parameter', 'paid');
        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.cookie.name', 'setono_sylius_partner_ads_cookie');
        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.cookie.expire', 40);

        // proves registerResources() ran
        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.model.program.class');
        $this->assertContainerBuilderHasParameter('setono_sylius_partner_ads.model.conversion.class');

        // proves services.php was loaded
        $this->assertContainerBuilderHasService(OrderTotalCalculator::class);
    }

    #[Test]
    public function it_prepends_the_admin_grids(): void
    {
        $container = new ContainerBuilder();
        (new SetonoSyliusPartnerAdsExtension())->prepend($container);

        self::assertSame([[
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
        ]], $container->getExtensionConfig('sylius_grid'));
    }
}
