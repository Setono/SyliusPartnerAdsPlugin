<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculator;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Client\Client;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Command\ProcessConversionsCommand;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandler;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\CreateConversionSubscriber;
use Setono\SyliusPartnerAdsPlugin\EventListener\SetCookieSubscriber;
use Setono\SyliusPartnerAdsPlugin\Form\Type\ProgramType;
use Setono\SyliusPartnerAdsPlugin\Menu\AdminMenuListener;
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

    // PSR-17 request factory, discovered from whichever PSR-17 implementation the application provides
    $services->set('setono_sylius_partner_ads.http_client.request_factory', RequestFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findRequestFactory']);

    $services->set(Client::class)
        ->args([
            // alias to the configured PSR-18 client, created by RegisterHttpClientPass
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

    $services->set(CreateConversionSubscriber::class)
        ->args([
            service('request_stack'),
            service(CookieHandlerInterface::class),
            // registered by the resource bundle
            service('setono_sylius_partner_ads.factory.conversion'),
            service('setono_sylius_partner_ads.repository.conversion'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ProcessConversionsCommand::class)
        ->args([
            service('setono_sylius_partner_ads.repository.conversion'),
            service('setono_sylius_partner_ads.repository.program'),
            service(ClientInterface::class),
            service(OrderTotalCalculatorInterface::class),
            service('doctrine'),
            service('lock.factory'),
            '%setono_sylius_partner_ads.notify_when%',
        ])
        ->tag('console.command');

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

    $services->set(AdminMenuListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'sylius.menu.admin.main',
            'method' => 'addAdminMenuItems',
        ]);
};
