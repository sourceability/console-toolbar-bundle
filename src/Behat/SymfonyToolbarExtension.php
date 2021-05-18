<?php

namespace Sourceability\ConsoleToolbarBundle\Behat;

use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Sourceability\ConsoleToolbarBundle\Behat\Listener\ProfilerToolbarListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SymfonyToolbarExtension implements ExtensionInterface
{
    public function process(ContainerBuilder $container): void
    {
    }

    public function getConfigKey()
    {
        return 'sourceability_symfony_toolbar';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(ProfilerToolbarListener::class, [new Reference(SymfonyExtension::KERNEL_ID)]);
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
        $container->setDefinition(ProfilerToolbarListener::class, $definition);
    }
}
