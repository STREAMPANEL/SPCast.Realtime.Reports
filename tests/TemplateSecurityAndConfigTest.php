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
}

