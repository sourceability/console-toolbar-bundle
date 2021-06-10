<?php

namespace Sourceability\ConsoleToolbarBundle\Console;

use function array_diff_key;
use function array_fill;
use function array_flip;
use function array_merge;
use function array_pop;
use function array_search;
use function mb_strlen;
use function parse_str;
use function parse_url;
use function preg_replace;
use function sprintf;
use Symfony\Bundle\WebProfilerBundle\Profiler\TemplateManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use function trim;
use Twig\Environment;
use function usort;

class ProfilerToolbarRenderer
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var RequestContext
     */
    private $originalRouterContext;

    /**
     * @var array<mixed>
     */
    private $templates;

    /**
     * @var array<string>
     */
    private $hiddenPanels;

    /**
     * @var int
     */
    private $maxColumnWidth;

    /**
     * @param array<mixed>  $templates
     * @param array<string> $hiddenPanels
     */
    public function __construct(
        Router $router,
        Profiler $profiler,
        Environment $twig,
        array $templates,
        array $hiddenPanels,
        int $maxColumnWidth = 30
    ) {
        $this->router = $router;
        $this->profiler = $profiler;
        $this->twig = $twig;
        $this->templates = $templates;
        $this->hiddenPanels = $hiddenPanels;
        $this->maxColumnWidth = $maxColumnWidth;
        $this->originalRouterContext = clone $router->getContext(); // clone is important here, we're making a copy
    }

    /**
     * @param array<Profile> $profiles
     */
    public function render(OutputInterface $output, array $profiles): void
    {
        if (\count($profiles) < 1) {
            return;
        }

        // Sort by date ascending
        usort(
            $profiles,
            static function (Profile $left, Profile $right): int {
                return $left->getTime() <=> $right->getTime();
            }
        );

        $table = new Table($output);
        $table->setStyle('box');

        $originalHeaders = $headers = ['Type', 'Name'];
        $rows = [];
        foreach ($profiles as $profile) {
            $webProfilerUrl = $this->generateUrlFixed(function () use ($profile): string {
                return $this->router->generate(
                    '_profiler',
                    [
                        'token' => $profile->getToken(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            });

            $rows[] = $this->renderRow($profile, $webProfilerUrl, $headers, $originalHeaders);
            $rows[] = new TableSeparator();
        }

        $table->setHeaders($headers);
        foreach ($headers as $headerIndex => $header) {
            $table->setColumnMaxWidth($headerIndex, $this->maxColumnWidth);
        }

        array_pop($rows); // remove last table sep

        $table->setRows($rows);
        $table->render();
    }

    /**
     * @return array<int, string>
     */
    private function renderRow(Profile $profile, string $webProfilerUrl, array &$headers, array $originalHeaders): array
    {
        $row = [
            $this->link($profile->getMethod() ?? '', $webProfilerUrl),
            $this->link($this->urlRemoveBeforePath($profile->getUrl() ?? ''), $webProfilerUrl),
        ];

        // make sure cells are aligned with headers
        $row = array_merge($row, array_fill(0, \count($headers) - \count($originalHeaders), ''));

        $panels = $this->getWebToolbarPanels($profile);
        $panels = array_diff_key($panels, array_flip($this->hiddenPanels));

        foreach ($panels as $panel => $text) {
            $headerName = $panel;

            if (mb_strlen($headerName) > $this->maxColumnWidth) {
                $headerName = substr($headerName, 0, $this->maxColumnWidth - 3) . '...';
            }

            $panelIndex = array_search($headerName, $headers, true);

            if (false === $panelIndex) {
                $headers[] = $headerName;
                $panelIndex = \count($headers) - 1;
            } else {
                $panelIndex = (int) $panelIndex;
            }

            $row[$panelIndex] = $this->link($text, sprintf('%s?panel=%s', $webProfilerUrl, $panel));
        }

        return $row;
    }

    /**
     * @return array<string, string>
     */
    private function getWebToolbarPanels(Profile $profile): array
    {
        $templateManager = new TemplateManager($this->profiler, $this->twig, $this->templates);

        $toolbar = $this->twig->render('@WebProfiler/Profiler/toolbar.html.twig', [
            'request' => null,
            'profile' => $profile,
            'templates' => $templateManager->getNames($profile),
            'profiler_url' => $profile->getUrl(),
            'token' => $profile->getToken(),
            'profiler_markup_version' => 2,
            // 1 = original toolbar, 2 = Symfony 2.8+ toolbar
            'csp_script_nonce' => null,
            'csp_style_nonce' => null,
        ]);

        $crawler = new Crawler();
        $crawler->addContent($toolbar);

        $panels = [];
        foreach ($crawler->filter('.sf-toolbar-block') as $toolbarBlock) {
            $toolbarBlock = new Crawler($toolbarBlock);

            $panelLink = $toolbarBlock->filter('a[href*="panel="]');

            if ($panelLink->count() < 1) {
                continue;
            }

            $href = $panelLink->first()
                ->attr('href')
            ;

            if (null === $href) {
                continue;
            }

            $parsedUrl = parse_url($href);

            if (false === $parsedUrl
                || !\array_key_exists('query', $parsedUrl)
            ) {
                continue;
            }

            parse_str($parsedUrl['query'], $query);

            $panel = (string) $query['panel'];

            if (\array_key_exists($panel, $panels)) {
                // time has 2 "blocks", so let's no override response time with peak memory
                continue;
            }

            $panels[$panel] = $this->removeWhiteSpaces($toolbarBlock->filter('.sf-toolbar-icon')->text());
        }

        return $panels;
    }

    private function link(string $text, string $url): string
    {
        if (mb_strlen($text) > $this->maxColumnWidth) {
            return $text;
        }

        return sprintf('<href=%s>%s</>', $url, $text);
    }

    private function urlRemoveBeforePath(string $url): string
    {
        return preg_replace('#^https?://(?:sa-web-profiler.localhost/|[^/]+)#', '', $url) ?? '';
    }

    private function removeWhiteSpaces(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function generateUrlFixed(callable $generateUrl): string
    {
        $context = $this->router->getContext();

        // Otherwise generated urls might look like http://application_test/login instead of http://localhost:8200/app_test.php/login
        $this->router->setContext($this->originalRouterContext);

        $result = $generateUrl();

        $this->router->setContext($context);

        return $result;
    }
}
