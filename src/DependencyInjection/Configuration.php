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
            ->children()
                ->scalarNode('partner_id_query_parameter')
                    ->defaultValue('paid')
                    ->info('This is the name of the query parameter that Partner Ads will append to your links when sending traffic to your site')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
