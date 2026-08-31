<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\DependencyInjection\Compiler;

use Buzz\Client\Curl;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterHttpClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterHttpClientPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterHttpClientPass());
    }

    #[Test]
    public function http_client_service_is_registered(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', 'http_client');
        $this->registerService('http_client', Curl::class);

        $this->compile();

        $this->assertContainerBuilderHasService('setono_sylius_partner_ads.http_client', Curl::class);
    }

    #[Test]
    public function it_accepts_a_service_id_with_a_leading_at_sign(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', '@http_client');
        $this->registerService('http_client', Curl::class);

        $this->compile();

        $this->assertContainerBuilderHasAlias('setono_sylius_partner_ads.http_client', 'http_client');
    }

    #[Test]
    public function throws_exception_if_http_client_service_does_not_exist(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', 'http_client');

        $this->expectException(ServiceNotFoundException::class);

        $this->compile();
    }

    #[Test]
    public function autoregister_http_client_if_buzz_is_present(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', null);

        $this->compile();

        $this->assertContainerBuilderHasService('setono_sylius_partner_ads.http_client', Curl::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'setono_sylius_partner_ads.http_client',
            0,
            new Reference('setono_sylius_partner_ads.http_client.response_factory'),
        );
        // Buzz defaults to no timeout at all, so the pass must set one explicitly
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'setono_sylius_partner_ads.http_client',
            1,
            ['timeout' => 30],
        );
    }
}
