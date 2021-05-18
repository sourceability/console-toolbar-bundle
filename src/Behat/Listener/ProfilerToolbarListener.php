<?php

namespace Sourceability\ConsoleToolbarBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Sourceability\ConsoleToolbarBundle\Console\ProfilerToolbarRenderer;
use Sourceability\ConsoleToolbarBundle\Profiler\RecentProfileLoader;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use function array_filter;
use function count;
use function in_array;
use function max;
use function sprintf;
use function time;

class ProfilerToolbarListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::BEFORE => ['beforeScenario', 10],
            StepTested::BEFORE => ['beforeAfterStep', 10],
            StepTested::AFTER => ['beforeAfterStep', 10],
            ScenarioTested::AFTER => ['afterScenario', 10],
        ];
    }

    private RecentProfileLoader $recentProfileLoader;
    private ProfilerToolbarRenderer $profilerToolbarRenderer;
    private Router $router;
    private RequestContext $originalRouterContext;

    private ?int $beforeScenarioTimestamp = null;
    private ?int $lastProfileTimestamp = null;

    /**
     * @var array<string>
     */
    private array $profileTokensShown = [];

    public function __construct(KernelInterface $kernel)
    {
        $this->recentProfileLoader = $kernel->getContainer()->get('sourceability.console_toolbar.profiler.recent_profile_loader');
        $this->profilerToolbarRenderer = $kernel->getContainer()->get('sourceability.console_toolbar.console.profiler_toolbar_renderer');
        $this->router = $kernel->getContainer()->get('router');
        $this->originalRouterContext = clone $this->router->getContext(); // clone is important here, we're making a copy
    }

    public function beforeScenario(ScenarioTested $event): void
    {
        $this->beforeScenarioTimestamp = time();
        $this->lastProfileTimestamp = time();
        $this->profileTokensShown = [];
    }

    public function beforeAfterStep(StepTested $event): void
    {
        $profiles = $this->recentProfileLoader->loadSince($this->lastProfileTimestamp);

        $profiles = array_filter(
            $profiles,
            fn (Profile $newProfile) => !in_array($newProfile->getToken(), $this->profileTokensShown, true)
        );

        if (count($profiles) > 0) {
            $this->profilerToolbarRenderer->render(new ConsoleOutput(), $profiles);
        }

        foreach ($profiles as $profile) {
            $this->lastProfileTimestamp = max(
                $this->lastProfileTimestamp ?? 0,
                $profile->getTime()
            );
            $this->profileTokensShown[] = $profile->getToken();
        }
    }

    public function afterScenario(ScenarioTested $event): void
    {
        $from = $this->beforeScenarioTimestamp;
        $to = time();

        $profileCount = $this->recentProfileLoader->countSince($from);

        if ($profileCount < 1) {
            return;
        }

        $profilerUrl = $this->generateUrlFixed(function () use ($from, $to): string {
            return $this->router->generate(
                '_profiler_search',
                [
                    'start' => $from,
                    'end' => $to,
                    'limit' => 100, // required otherwise list has no items
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        });

        $output = new ConsoleOutput();
        $output->writeln(
            sprintf(
                '%d profiles collected, <href=%s>Open profile list</>',
                $profileCount,
                $profilerUrl
            )
        );
    }

    private function generateUrlFixed(callable $generateUrl): string
    {
        $context = $this->router->getContext();

        // Otherwise generated urls will look like http://application_test/login
        // instead of http://localhost:8200/app_test.php/login
        $this->router->setContext($this->originalRouterContext);

        $result = $generateUrl();

        $this->router->setContext($context);

        return $result;
    }
}
