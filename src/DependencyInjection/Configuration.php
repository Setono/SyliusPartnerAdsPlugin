<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Setono\SyliusPartnerAdsPlugin\Doctrine\ORM\ConversionRepository;
use Setono\SyliusPartnerAdsPlugin\Doctrine\ORM\ProgramRepository;
use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Form\Type\ProgramType;
use Setono\SyliusPartnerAdsPlugin\Model\Conversion;
use Setono\SyliusPartnerAdsPlugin\Model\Program;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_partner_ads');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('http_client')
                    ->cannotBeEmpty()
                    ->defaultValue('psr18.http_client')
                    ->info('The service id of the PSR-18 HTTP client used to notify Partner Ads. The default is the client Symfony registers when symfony/http-client is installed')
                ->end()
                ->scalarNode('driver')->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)->cannotBeEmpty()->end()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('program')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Program::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(ProgramRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                        ->scalarNode('form')->defaultValue(ProgramType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('conversion')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Conversion::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(ConversionRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('urls')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('notify')
                            ->cannotBeEmpty()
                            ->defaultValue('https://www.partner-ads.com/dk/leadtracks2s.php?programid={program_id}&type=salg&partnerid={partner_id}&userip={ip}&ordreid={order_id}&varenummer=x&antal=1&omprsalg={value}')
                            ->info('The URL to use when notifying Partner Ads of a new order. Remember to include the variables')
                        ->end()
                    ->end()
                ->end()
                ->enumNode('notify_when')
                    ->values(NotifyWhen::values())
                    ->defaultValue(NotifyWhen::Completed->value)
                    ->info('When to notify Partner Ads about a conversion: "completed" (the default) notifies as soon as the order is placed, "paid" only when the order has been paid')
                ->end()
                ->scalarNode('query_parameter')
                    ->cannotBeEmpty()
                    ->defaultValue('paid')
                    ->info('This is the name of the query parameter that Partner Ads will append to your links when sending traffic to your site')
                ->end()
                ->integerNode('attribution_window')
                    ->min(1)
                    ->defaultValue(40)
                    ->example('40')
                    ->info('The number of days after clicking an affiliate link during which an order is attributed to the partner. Partner Ads\' official docs says it should be 40')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
