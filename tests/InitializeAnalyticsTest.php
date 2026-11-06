<?php
declare(strict_types=1);

namespace Tests;

class InitializeAnalyticsTest extends TestCase
{
    private string|false $originalEnv;

    protected function setUp(): void
    {
        $this->originalEnv = getenv('GOOGLE_APPLICATION_CREDENTIALS');

        if (!function_exists('initializeAnalytics')) {
            require_once __DIR__ . '/../includes/initializeAnalytics.php';
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv !== false) {
            putenv("GOOGLE_APPLICATION_CREDENTIALS={$this->originalEnv}");
        } else {
            putenv('GOOGLE_APPLICATION_CREDENTIALS');
        }

        initializeAnalytics(false, true);
    }

    public function testInitializeAnalyticsWithInjectedClient(): void
    {
        $clientMock = $this->createMock(\Google_Client::class);

        $analytics = initializeAnalytics($clientMock, true);

        $this->assertInstanceOf(\Google_Service_AnalyticsData::class, $analytics);

        $analytics2 = initializeAnalytics();
        $this->assertSame($analytics, $analytics2);
    }

    public function testEnsureSecureCacheDirectory(): void
    {
        $uniqueId = uniqid();
        $baseDir = sys_get_temp_dir() . '/test_cache_' . $uniqueId;
        $dir = $baseDir . '/';

        if (is_dir($dir)) {
            $this->recursiveDelete($dir);
        }

        ensureSecureCacheDirectory($dir);

        $this->assertDirectoryExists($dir);
        $this->assertTrue(is_readable($dir));
        $this->assertTrue(is_writable($dir));

        $htaccess = $dir . '.htaccess';
        $this->assertFileExists($htaccess);
        $content = (string) file_get_contents($htaccess);
        $this->assertStringContainsString('Order Allow,Deny', $content);
        $this->assertStringContainsString('Deny from all', $content);

        $this->recursiveDelete($dir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff((array) scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
