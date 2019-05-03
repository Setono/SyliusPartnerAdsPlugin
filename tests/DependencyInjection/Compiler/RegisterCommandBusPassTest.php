<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterCommandBusPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\MessageBus;

final class RegisterCommandBusPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterCommandBusPass());
    }

    /**
     * @test
     */
    public function command_bus_service_exists()
    {
        $this->setParameter('setono_sylius_partner_ads.messenger.command_bus', 'message_bus');
        $this->registerService('message_bus', MessageBus::class);

        $this->compile();

        $this->assertContainerBuilderHasService('setono_sylius_partner_ads.command_bus', MessageBus::class);
    }
}
