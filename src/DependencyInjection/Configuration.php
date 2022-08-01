<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Buzz\Client\BuzzClientInterface;
use Setono\SyliusPartnerAdsPlugin\Doctrine\ORM\ProgramRepository;
use Setono\SyliusPartnerAdsPlugin\Form\Type\ProgramType;
use Setono\SyliusPartnerAdsPlugin\Model\Program;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_partner_ads');

        $rootNode = $treeBuilder->getRootNode();

        /** @psalm-suppress MixedMethodCall,PossiblyNullReference,PossiblyUndefinedMethod */
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->append($this->addHttpClientNode())
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
                ->scalarNode('query_parameter')
                    ->cannotBeEmpty()
                    ->defaultValue('paid')
                    ->info('This is the name of the query parameter that Partner Ads will append to your links when sending traffic to your site')
                ->end()
                ->arrayNode('cookie')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')
                            ->cannotBeEmpty()
                            ->defaultValue('setono_sylius_partner_ads_cookie')
                            ->example('partner_ads')
                            ->info('The name of the cookie')
                        ->end()
                        ->integerNode('expire')
                            ->min(0)
                            ->defaultValue(40)
                            ->example(40)
                            ->info('The number of days before the cookie expires. Partner Ads\' official docs says it should be 40')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('messenger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('command_bus')
                            ->cannotBeEmpty()
                            ->defaultValue('sylius_default.bus')
                            ->example('sylius_default.bus')
                            ->info('The service id for the message bus you use for commands')
                        ->end()
                        ->scalarNode('transport')
                            ->cannotBeEmpty()
                            ->defaultNull()
                            ->info('The transport to use if you would like HTTP requests to be async (which is a very good choice on production)')
                            ->example('amqp')
                        ->end()
                    ->end()

                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    public function addHttpClientNode(): ScalarNodeDefinition
    {
        $treeBuilder = new TreeBuilder('http_client', 'scalar');

        $node = $treeBuilder->getRootNode();
        $node
            ->cannotBeEmpty()
            ->info('The service id for your PSR18 HTTP client')
        ;

        if (interface_exists(BuzzClientInterface::class)) {
            $node->defaultNull();
        } else {
            $node->isRequired();
        }

        return $node;
    }
}
