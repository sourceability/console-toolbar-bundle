<?php

namespace Sourceability\ConsoleToolbarBundle\DependencyInjection;

use Sourceability\ConsoleToolbarBundle\Console\ProfilerToolbarRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class SourceabilityConsoleToolbarExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $container
            ->getDefinition(ProfilerToolbarRenderer::class)
            ->replaceArgument('$hiddenPanels', $mergedConfig['toolbar']['hidden_panels'])
            ->replaceArgument('$maxColumnWidth', $mergedConfig['toolbar']['max_column_width'])
        ;
    }
}
