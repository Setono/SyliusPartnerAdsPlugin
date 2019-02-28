<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('setono_sylius_partner_ads');
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('program_id')
                    ->isRequired()
                    ->min(1)
                    ->info('This is your programs id in Partner Ads')
                ->end()
                ->scalarNode('query_parameter')
                    ->defaultValue('paid')
                    ->info('This is the name of the query parameter that Partner Ads will append to your links when sending traffic to your site')
                ->end()
                ->arrayNode('urls')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('notify')
                            ->defaultValue('https://www.partner-ads.com/dk/leadtracks2s.php?programid=$program_id&type=salg&partnerid=$partner_id&userip=$ip&ordreid=$order_id&varenummer=x&antal=1&omprsalg=$order_total')
                            ->info('The URL to call when an order is completed by the customer')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cookie')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')
                            ->defaultValue('setono_sylius_partner_ads_cookie')
                            ->example('partner_ads')
                            ->info('The name of the cookie')
                        ->end()
                        ->integerNode('expire')
                            ->defaultValue(40)
                            ->example(40)
                            ->info('The number of days before the cookie expires. Partner Ads\' official docs says it should be 40')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
