<?php

namespace Sourceability\ConsoleToolbarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
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
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
