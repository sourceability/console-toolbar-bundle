<?php

namespace Sourceability\ConsoleToolbarBundle\PHPUnit;

use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;
use Sourceability\ConsoleToolbarBundle\Console\IndentedConsoleOutput;
use Sourceability\ConsoleToolbarBundle\Console\ProfilerToolbarRenderer;
use Sourceability\ConsoleToolbarBundle\Profiler\RecentProfileLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @internal
 * @coversNothing
 */
final class ConsoleToolbarExtension extends KernelTestCase // easiest way to get a kernel instance
    implements BeforeTestHook, AfterTestHook
{
    /**
     * @var int|null
     */
    private $lastProfileTimestamp;

    /**
     * @var array<string>
     */
    private $profileTokensShown = [];

    /**
     * @var bool
     */
    private $alwaysShow;

    /**
     * @var int
     */
    private $indentSpaces;

    public function __construct(bool $alwaysShow = true, int $indent = 4)
    {
        parent::__construct(null, [], '');

        $this->alwaysShow = $alwaysShow;
        $this->indentSpaces = $indent;
    }

    public function executeBeforeTest(string $test): void
    {
        $this->lastProfileTimestamp = time();
        $this->profileTokensShown = [];
    }

    public function executeAfterTest(string $test, float $time): void
    {
        $toolbar = (bool) getenv('TOOLBAR');

        if (!$this->alwaysShow
            && !$toolbar
        ) {
            return;
        }

        $kernel = self::bootKernel();

        $recentProfileLoader = $kernel->getContainer()
            ->get('sourceability.console_toolbar.profiler.recent_profile_loader')
        ;
        $profilerToolbarRenderer = $kernel->getContainer()
            ->get('sourceability.console_toolbar.console.profiler_toolbar_renderer')
        ;

        \assert($recentProfileLoader instanceof RecentProfileLoader);
        \assert($profilerToolbarRenderer instanceof ProfilerToolbarRenderer);

        $profiles = $recentProfileLoader->loadSince($this->lastProfileTimestamp);

        $profiles = array_filter(
            $profiles,
            function (Profile $newProfile): bool {
                return !\in_array($newProfile->getToken(), $this->profileTokensShown, true);
            }
        );

        if (\count($profiles) > 0) {
            $output = new IndentedConsoleOutput($this->indentSpaces);
            $output->writeln(''); // make sure the table is fully displayed/aligned

            $profilerToolbarRenderer->render($output, $profiles);
        }

        foreach ($profiles as $profile) {
            $this->lastProfileTimestamp = max($this->lastProfileTimestamp ?? 0, $profile->getTime());
            $this->profileTokensShown[] = $profile->getToken();
        }

        self::ensureKernelShutdown(); // make sure we don't interfere with WebTestCase as the kernel is shared
    }
}
