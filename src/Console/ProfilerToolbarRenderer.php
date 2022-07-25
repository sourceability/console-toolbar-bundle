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
use Sourceability\ConsoleToolbarBundle\Console\Model\ToolbarCell;
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
use Symfony\Component\Routing\RouterInterface;
use function trim;
use Twig\Environment;
use function usort;

class ProfilerToolbarRenderer
{
    /**
     * @var RouterInterface
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
        RouterInterface $router,
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
     * @param array<string|int, string> $headers
     * @param array<string|int, string> $originalHeaders
     *
     * @return array<int, string>
     */
    private function renderRow(Profile $profile, string $webProfilerUrl, array &$headers, array $originalHeaders): array
    {
        $row = [
            $this->link($profile->getMethod() ?? '', $webProfilerUrl),
            $this->link($this->urlRemoveBeforePath($profile->getUrl() ?? ''), $webProfilerUrl),
        ];

        // make sure cells are aligned with headers
        $fillCount = \count($headers) - \count($originalHeaders);
        \assert($fillCount >= 0);
        $row = array_merge($row, array_fill(0, $fillCount, ''));

        $toolbarCells = $this->getWebToolbarCells($profile);
        $toolbarCells = array_diff_key($toolbarCells, array_flip($this->hiddenPanels));

        foreach ($toolbarCells as $panel => $toolbarCell) {
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

            $row[$panelIndex] = $this->link(
                $toolbarCell->getText(),
                sprintf('%s?panel=%s', $webProfilerUrl, $panel),
                $toolbarCell->getColor()
            );
        }

        return $row;
    }

    /**
     * @return array<string, ToolbarCell>
     */
    private function getWebToolbarCells(Profile $profile): array
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
            'full_stack' => class_exists('Symfony\Bundle\FullStack'),
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

            $matches = [];
            preg_match('#sf-toolbar-status-(?P<color>[a-zA-Z]+)#', $toolbarBlock->html(), $matches);
            $color = $matches['color'] ?? null;
            
            if ('grey' === $color) {
                $color = 'default';
            }

            $panels[$panel] = new ToolbarCell(
                $this->removeWhiteSpaces($toolbarBlock->filter('.sf-toolbar-icon')->text()),
                $color
            );
        }

        return $panels;
    }

    private function link(string $text, string $url, ?string $color = null): string
    {
        $parts = [];

        if (mb_strlen($text) <= $this->maxColumnWidth) {
            $parts[] = sprintf('href=%s', $url);
        }

        if (null !== $color) {
            $parts[] = sprintf('fg=%s', $color);
        }

        if (\count($parts) < 1) {
            return $text;
        }

        return sprintf('<%s>%s</>', implode(';', $parts), $text);
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
