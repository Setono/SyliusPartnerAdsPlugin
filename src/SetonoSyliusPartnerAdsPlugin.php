<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin;

use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterAsyncNotifierPass;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterCommandBusPass;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterDefaultNotifierPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SetonoSyliusPartnerAdsPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterCommandBusPass());
        $container->addCompilerPass(new RegisterAsyncNotifierPass());
        $container->addCompilerPass(new RegisterDefaultNotifierPass());
    }
}
