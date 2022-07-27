<?php

namespace Sourceability\ConsoleToolbarBundle\EventListener;

use Sourceability\ConsoleToolbarBundle\Console\ProfilerToolbarRenderer;
use Sourceability\ConsoleToolbarBundle\Profiler\RecentProfileLoader;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This adds a new global option: --toolbar When running a command with --toolbar, the ascii toolbar will be displayed
 * on stderr.
 */
class ConsoleToolbarListener implements EventSubscriberInterface
{
    /**
     * @var ProfilerToolbarRenderer
     */
    private $toolbarRenderer;

    /**
     * @var RecentProfileLoader
     */
    private $recentProfileLoader;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(
        ProfilerToolbarRenderer $toolbarRenderer,
        RecentProfileLoader $recentProfileLoader,
        KernelInterface $kernel
    ) {
        $this->toolbarRenderer = $toolbarRenderer;
        $this->recentProfileLoader = $recentProfileLoader;
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::TERMINATE => ['onTerminate', -1024],
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (null === $command
            || null === $command->getApplication()
        ) {
            return;
        }

        // See https://github.com/symfony/symfony/pull/15938

        $toolbarOption = new InputOption(
            'toolbar',
            null,
            InputOption::VALUE_NONE,
            'Display the symfony profiler toolbar',
            null
        );

        $definitions = [
            $command->getApplication()
                ->getDefinition(),
            $command->getDefinition(), // because \Symfony\Component\Console\Command\Command::mergeApplicationDefinition has already been called
        ];
        foreach ($definitions as $definition) {
            $definition->addOption($toolbarOption);
        }
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $input = $event->getInput();
        $output = $event->getOutput();

        $displayToolbar = $input->hasOption('toolbar') && (bool) $input->getOption('toolbar');

        if (!$displayToolbar) {
            return;
        }

        $profiles = $this->recentProfileLoader->loadSince((int) $this->kernel->getStartTime());

        if (\count($profiles) < 1) {
            // kind of weird, maybe deserves an error/exception ?
            return;
        }

        if ($output instanceof ConsoleOutputInterface) {
            // If possible, output to stderr to not mess with simple pipes
            $output = $output->getErrorOutput();
        }

        $this->toolbarRenderer->render($output, $profiles);
    }
}
