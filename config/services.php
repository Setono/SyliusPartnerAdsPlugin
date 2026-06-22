<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculator;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Client\Client;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContext;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandler;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\NotifySubscriber;
use Setono\SyliusPartnerAdsPlugin\EventListener\SetCookieSubscriber;
use Setono\SyliusPartnerAdsPlugin\Form\Type\ProgramType;
use Setono\SyliusPartnerAdsPlugin\Menu\AdminMenuListener;
use Setono\SyliusPartnerAdsPlugin\Message\Handler\NotifyHandler;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProvider;
use Setono\SyliusPartnerAdsPlugin\UrlProvider\NotifyUrlProviderInterface;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('setono_sylius_partner_ads.form.program.validation_groups', ['setono_sylius_partner_ads']);

    $services = $container->services();

    $services->set(OrderTotalCalculator::class);
    $services->alias(OrderTotalCalculatorInterface::class, OrderTotalCalculator::class);

    $services->set(NotifyUrlProvider::class)
        ->args([
            '%setono_sylius_partner_ads.urls.notify%',
        ]);
    $services->alias(NotifyUrlProviderInterface::class, NotifyUrlProvider::class);

    // PSR-17 factories, discovered from whichever PSR-17 implementation the application provides.
    // These ids are referenced by the Client and by RegisterHttpClientPass.
    $services->set('setono_sylius_partner_ads.http_client.request_factory', RequestFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findRequestFactory']);
    $services->set('setono_sylius_partner_ads.http_client.response_factory', ResponseFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findResponseFactory']);

    $services->set(Client::class)
        ->args([
            // alias created by RegisterHttpClientPass (a configured PSR-18 client or a Buzz fallback)
            service('setono_sylius_partner_ads.http_client'),
            service('setono_sylius_partner_ads.http_client.request_factory'),
            service(NotifyUrlProviderInterface::class),
        ]);
    $services->alias(ClientInterface::class, Client::class);

    $services->set(CookieHandler::class)
        ->args([
            '%setono_sylius_partner_ads.cookie.name%',
            '%setono_sylius_partner_ads.cookie.expire%',
        ]);
    $services->alias(CookieHandlerInterface::class, CookieHandler::class);

    $services->set(ProgramContext::class)
        ->args([
            service('sylius.context.channel.composite'),
            // repository service created by AbstractResourceExtension::registerResources()
            service('setono_sylius_partner_ads.repository.program'),
        ]);
    $services->alias(ProgramContextInterface::class, ProgramContext::class);

    $services->set(NotifySubscriber::class)
        ->args([
            // alias created by RegisterCommandBusPass
            service('setono_sylius_partner_ads.command_bus'),
            service(CookieHandlerInterface::class),
            service(OrderTotalCalculatorInterface::class),
            service(ProgramContextInterface::class),
            service('sylius.repository.order'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(SetCookieSubscriber::class)
        ->args([
            service(CookieHandlerInterface::class),
            '%setono_sylius_partner_ads.query_parameter%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ProgramType::class)
        ->args([
            '%setono_sylius_partner_ads.model.program.class%',
            '%setono_sylius_partner_ads.form.program.validation_groups%',
        ])
        ->tag('form.type');

    $services->set(NotifyHandler::class)
        ->args([
            service(ClientInterface::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(AdminMenuListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'sylius.menu.admin.main',
            'method' => 'addAdminMenuItems',
        ]);
};
