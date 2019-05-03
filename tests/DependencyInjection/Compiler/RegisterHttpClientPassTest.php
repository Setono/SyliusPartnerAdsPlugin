<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Buzz\Client\Curl;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterHttpClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class RegisterHttpClientPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterHttpClientPass());
    }

    /**
     * @test
     */
    public function http_client_service_is_registered(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', 'http_client');
        $this->registerService('http_client', Curl::class);

        $this->compile();

        $this->assertContainerBuilderHasService('setono_sylius_partner_ads.http_client', Curl::class);
    }

    /**
     * @test
     */
    public function throws_exception_if_http_client_service_does_not_exist(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', 'http_client');

        $this->expectException(ServiceNotFoundException::class);

        $this->compile();
    }

    /**
     * @test
     */
    public function autoregister_http_client_if_buzz_is_present(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', null);

        $this->compile();

        $this->assertContainerBuilderHasService('setono_sylius_partner_ads.http_client', Curl::class);
    }
}
