<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin;

use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterCommandBusPass;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterHttpClientPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusPartnerAdsPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterCommandBusPass());
        $container->addCompilerPass(new RegisterHttpClientPass());
    }

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }
}
