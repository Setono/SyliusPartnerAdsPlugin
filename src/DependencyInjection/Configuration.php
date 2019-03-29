<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\DependencyInjection;

use Setono\SyliusPartnerAdsPlugin\Doctrine\ORM\ProgramRepository;
use Setono\SyliusPartnerAdsPlugin\Form\Type\ProgramType;
use Setono\SyliusPartnerAdsPlugin\Model\Program;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('setono_sylius_partner_ads');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for <= SF 4.1
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('setono_sylius_partner_ads');
        }

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
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
                                        ->scalarNode('interface')->defaultValue(ProgramInterface::class)->cannotBeEmpty()->end()
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
                ->scalarNode('query_parameter')
                    ->cannotBeEmpty()
                    ->defaultValue('paid')
                    ->info('This is the name of the query parameter that Partner Ads will append to your links when sending traffic to your site')
                ->end()
                ->arrayNode('urls')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('notify')
                            ->cannotBeEmpty()
                            ->defaultValue('https://www.partner-ads.com/dk/leadtracks2s.php?programid=$program_id&type=salg&partnerid=$partner_id&userip=$ip&ordreid=$order_id&varenummer=x&antal=1&omprsalg=$order_total')
                            ->info('The URL to call when an order is completed by the customer. You can use these variables in your URL: $program_id, $partner_id, $ip, $order_id, $order_total')
                        ->end()
                    ->end()
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
                    ->{interface_exists(MessageBusInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('command_bus')
                            ->cannotBeEmpty()
                            ->info('The service id for the message bus you use for commands')
                        ->end()
                        ->scalarNode('transport')
                            ->cannotBeEmpty()
                            ->info('The transport to use. If you only have one transport defined that transport will be used, else you have to define it here')
                            ->example('amqp')
                        ->end()
                    ->end()

                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
