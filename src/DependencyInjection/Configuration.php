<?php

namespace Sourceability\ConsoleToolbarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sourceability_console_toolbar');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('toolbar')
                    ->canBeEnabled()
                    ->children()
                        ->integerNode('max_column_width')
                            ->defaultValue(30)
                        ->end()
                        ->arrayNode('hidden_panels')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('base_url')
                            ->defaultValue('http://localhost')
                            ->info('This is not used, please set the router request context instead.')
                            ->setDeprecated()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
