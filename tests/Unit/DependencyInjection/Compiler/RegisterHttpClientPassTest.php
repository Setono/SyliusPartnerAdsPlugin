<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\DependencyInjection\Compiler\RegisterHttpClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class RegisterHttpClientPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterHttpClientPass());
    }

    #[Test]
    public function it_aliases_the_configured_http_client(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', 'http_client');
        $this->registerService('http_client', \stdClass::class);

        $this->compile();

        $this->assertContainerBuilderHasAlias('setono_sylius_partner_ads.http_client', 'http_client');
    }

    #[Test]
    public function it_accepts_a_service_id_with_a_leading_at_sign(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', '@http_client');
        $this->registerService('http_client', \stdClass::class);

        $this->compile();

        $this->assertContainerBuilderHasAlias('setono_sylius_partner_ads.http_client', 'http_client');
    }

    #[Test]
    public function it_throws_a_helpful_exception_when_the_http_client_service_does_not_exist(): void
    {
        $this->setParameter('setono_sylius_partner_ads.http_client', 'http_client');

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('symfony/http-client');

        $this->compile();
    }

    #[Test]
    public function it_does_nothing_when_the_plugin_is_not_configured(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService('setono_sylius_partner_ads.http_client');
    }
}
