<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Aliases the plugin's HTTP client to the configured PSR-18 client service. There is deliberately no bundled
 * fallback client: the application decides which HTTP client (and which timeouts) to use.
 */
final class RegisterHttpClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('setono_sylius_partner_ads.http_client')) {
            return;
        }

        /** @var string $serviceId */
        $serviceId = $container->getParameter('setono_sylius_partner_ads.http_client');

        // services are referenced with a leading '@' in service definitions, so it is an easy mistake to
        // make in the plugin configuration as well - accept it instead of failing with a confusing error
        $serviceId = ltrim($serviceId, '@');

        if (!$container->has($serviceId)) {
            throw new ServiceNotFoundException($serviceId, msg: sprintf(
                'The HTTP client service "%s" configured as setono_sylius_partner_ads.http_client does not exist. Install symfony/http-client (Symfony then registers "psr18.http_client", the default) or configure the id of another PSR-18 client.',
                $serviceId,
            ));
        }

        $container->setAlias('setono_sylius_partner_ads.http_client', $serviceId);
    }
}
