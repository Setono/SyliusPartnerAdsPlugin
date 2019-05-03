<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler;

use Buzz\Client\BuzzClientInterface;
use Buzz\Client\Curl;
use Setono\SyliusPartnerAdsPlugin\Exception\InterfaceNotFoundException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterHttpClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('setono_sylius_partner_ads.http_client')) {
            return;
        }

        $httpClientServiceId = 'setono_sylius_partner_ads.http_client';

        $httpClientServiceIdParam = $container->getParameter('setono_sylius_partner_ads.http_client');
        if (null === $httpClientServiceIdParam) {
            if (!interface_exists(BuzzClientInterface::class)) {
                throw new InterfaceNotFoundException(BuzzClientInterface::class);
            }

            $definition = new Definition(Curl::class, [
                new Reference('setono_sylius_partner_ads.http_client.response_factory'),
            ]);
            $container->setDefinition($httpClientServiceId, $definition);
        } else {
            if (!$container->has($httpClientServiceIdParam)) {
                throw new ServiceNotFoundException($httpClientServiceIdParam);
            }
            $container->setAlias($httpClientServiceId, $httpClientServiceIdParam);
        }
    }
}
