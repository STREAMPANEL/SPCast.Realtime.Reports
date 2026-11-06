<?php
declare(strict_types=1);

namespace Tests;

use App\ReportType;

class TemplateSecurityAndConfigTest extends TestCase
{
    public function testSrcAndTestsFilesDeclareStrictTypesImmediately(): void
    {
        $files = array_merge(
            glob(__DIR__ . '/../src/*.php') ?: [],
            glob(__DIR__ . '/*.php') ?: []
        );

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $this->assertIsArray($lines);
            $this->assertSame('<?php', $lines[0] ?? '', 'Invalid opening tag in ' . basename($file));
            $this->assertSame('declare(strict_types=1);', $lines[1] ?? '', 'Missing strict_types on line 2 in ' . basename($file));
        }
    }

    public function testNoHardcodedAnalyticsPropertyIdsOutsideConfig(): void
    {
        $files = array_merge(
            glob(__DIR__ . '/../*.php') ?: [],
            glob(__DIR__ . '/../includes/*.php') ?: [],
            glob(__DIR__ . '/../src/*.php') ?: []
        );

        $configRealPath = realpath(__DIR__ . '/../includes/config.php');

        foreach ($files as $file) {
            if (realpath($file) === $configRealPath) {
                continue;
            }
            $content = (string) file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression(
                '/[\'"]properties\/\d+[\'"]/',
                $content,
                'Hardcoded GA4 property ID found in ' . basename($file)
            );
        }
    }

    public function testAllStandaloneReportEntrypointsUseConfigConstants(): void
    {
        foreach (ReportType::cases() as $case) {
            $path = __DIR__ . '/../' . $case->pageUrl();
            $this->assertFileExists($path);

            $content = (string) file_get_contents($path);
            $this->assertStringContainsString("require_once 'includes/config.php';", $content);
            $this->assertStringContainsString("require_once 'includes/i18n.php';", $content);

            if ($case === ReportType::In) {
                $this->assertStringContainsString('GA_PROPERTY_ID_INBOUND', $content);
            } elseif ($case === ReportType::Out) {
                $this->assertStringContainsString('GA_PROPERTY_ID_OUTBOUND', $content);
            } else {
                $this->assertStringContainsString('GA_PROPERTY_ID_GENERAL', $content);
            }
        }
    }

    public function testHtmlAndApiJsonEncodeCallsIncludeHexSecurityFlags(): void
    {
        $files = array_merge(
            glob(__DIR__ . '/../*.php') ?: [],
            glob(__DIR__ . '/../includes/*.php') ?: []
        );

        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $idx => $line) {
                if (!str_contains($line, 'json_encode') || str_contains($line, 'file_put_contents')) {
                    continue;
                }
                if (preg_match('~^\s*(//|\*)~', $line) === 1) {
                    continue;
                }

                $location = basename($file) . ':' . ($idx + 1);
                $this->assertStringContainsString('JSON_HEX_TAG', $line, 'Missing JSON_HEX_TAG at ' . $location);
                $this->assertStringContainsString('JSON_HEX_AMP', $line, 'Missing JSON_HEX_AMP at ' . $location);
                $this->assertStringContainsString('JSON_HEX_APOS', $line, 'Missing JSON_HEX_APOS at ' . $location);
                $this->assertStringContainsString('JSON_HEX_QUOT', $line, 'Missing JSON_HEX_QUOT at ' . $location);
            }
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testStandaloneDimensionTemplateEscapesMaliciousCacheKeys(): void
    {
        $cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $periods = ['today', 'yesterday', '7days', '30days'];
        $createdFiles = [];
        $xssKey = '</script><script>alert("XSS")</script>';

        foreach ($periods as $period) {
            $cacheFile = $cacheDir . 'analytics_ipversion_cache_' . $period . '.json';
            file_put_contents($cacheFile, json_encode([$xssKey => 42]), LOCK_EX);
            $createdFiles[] = $cacheFile;
        }

        $previousCwd = getcwd();
        chdir(__DIR__ . '/..');

        ob_start();
        try {
            include __DIR__ . '/../ipversion.php';
            $html = (string) ob_get_clean();
        } finally {
            if ($previousCwd !== false) {
                chdir($previousCwd);
            }
            foreach ($createdFiles as $cacheFile) {
                if (is_file($cacheFile)) {
                    unlink($cacheFile);
                }
            }
        }

        $this->assertStringNotContainsString($xssKey, $html);
        $this->assertStringContainsString('\u003C', $html);
        $this->assertStringContainsString('container_KVNJAR2p_rewrite.js', $html);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCityPageDoesNotExposeSmallCachedGroups(): void
    {
        $html = $this->renderWithCityCache('city.php');

        $this->assertStringNotContainsString('Private Test City', $html);
        $this->assertStringContainsString('Public Test City', $html);
        $this->assertStringContainsString('"Andere":3', $html);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHomePageDoesNotExposeSmallCachedCityGroups(): void
    {
        $_GET['stat'] = 'city';
        $html = $this->renderWithCityCache('index.php');

        $this->assertStringNotContainsString('Private Test City', $html);
        $this->assertStringContainsString('Public Test City', $html);
        $this->assertMatchesRegularExpression('~<span>Andere</span>.*?<td[^>]*>3</td>~s', $html);
    }

    private function renderWithCityCache(string $page): string
    {
        $cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $saved = [];
        $lockFiles = [];
        foreach (['today', 'yesterday', '7days', '30days'] as $period) {
            $file = $cacheDir . "analytics_city_cache_{$period}.json";
            $saved[$file] = is_file($file)
                ? ['content' => file_get_contents($file), 'mtime' => filemtime($file)]
                : null;
            $lockFiles[$file . '.lock'] = is_file($file . '.lock');
            file_put_contents($file, json_encode(['Public Test City' => 8, 'Private Test City' => 3]));
        }

        $previousCwd = getcwd();
        chdir(__DIR__ . '/..');
        $initialBufferLevel = ob_get_level();
        ob_start();
        try {
            include __DIR__ . '/../' . $page;
            return (string) ob_get_clean();
        } finally {
            if (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }
            if ($previousCwd !== false) {
                chdir($previousCwd);
            }
            foreach ($saved as $file => $backup) {
                if ($backup === null) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                } else {
                    file_put_contents($file, $backup['content']);
                    touch($file, $backup['mtime']);
                }
            }
            foreach ($lockFiles as $file => $existed) {
                if (!$existed && is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public function testNoDeadExternalFaqLinksRemainInProjectFiles(): void
    {
        $files = array_merge(
            glob(__DIR__ . '/../*.php') ?: [],
            glob(__DIR__ . '/../includes/*.php') ?: [],
            glob(__DIR__ . '/../src/*.php') ?: [],
            [__DIR__ . '/../js/app.js']
        );

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                'faq/statistik',
                $content,
                'Dead FAQ URL reference found in ' . basename($file)
            );
            $this->assertStringNotContainsString(
                'faqLinkType',
                $content,
                'Obsolete faqLinkType reference found in ' . basename($file)
            );
        }
    }

    public function testFooterDoesNotContainRedundantSpcastEuLink(): void
    {
        $footerContent = (string) file_get_contents(__DIR__ . '/../includes/footer.php');
        $navContent = (string) file_get_contents(__DIR__ . '/../includes/nav.php');

        $this->assertStringNotContainsString('href="https://www.spcast.eu/"', $footerContent);
        $this->assertStringContainsString('https://www.spcast.eu/kontakt/impressum/', $footerContent);
        $this->assertStringContainsString('https://www.spcast.eu/kontakt/datenschutz/', $footerContent);
        $this->assertStringContainsString('https://www.spcast.eu/kontakt/cookies/', $footerContent);
        $this->assertStringContainsString('href="https://www.spcast.eu/"', $navContent);
    }
}

