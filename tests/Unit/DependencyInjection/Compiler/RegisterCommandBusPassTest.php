<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterCommandBusPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\MessageBus;

final class RegisterCommandBusPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterCommandBusPass());
    }

    #[Test]
    public function command_bus_service_exists(): void
    {
        $this->setParameter('setono_sylius_partner_ads.messenger.command_bus', 'message_bus');
        $this->registerService('message_bus', MessageBus::class);

        $this->compile();

        $this->assertContainerBuilderHasService('setono_sylius_partner_ads.command_bus', MessageBus::class);
    }
}
