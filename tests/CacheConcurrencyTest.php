<?php
declare(strict_types=1);

namespace Tests;

use App\AnalyticsService;
use App\Logger;

class CacheConcurrencyTest extends TestCase
{
    public function testConcurrentCacheMissesFetchOnlyOnce(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('The pcntl extension is required for the concurrency test.');
        }

        $dir = sys_get_temp_dir() . '/spcast_cache_concurrency_' . bin2hex(random_bytes(6));
        mkdir($dir, 0700);
        $cacheFile = $dir . '/report.json';
        $markersFile = $dir . '/fetches.log';
        $startFile = $dir . '/start';
        $service = new AnalyticsService(
            new \Google_Service_AnalyticsData(new \Google_Client()),
            new Logger($dir . '/error.log')
        );

        $children = [];
        $statuses = [];
        try {
            for ($i = 0; $i < 4; $i++) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    file_put_contents($dir . '/ready-' . getmypid(), '1');
                    while (!is_file($startFile)) {
                        usleep(1000);
                    }

                    $result = $service->getCachedData(
                        $cacheFile,
                        static function () use ($markersFile): array {
                            file_put_contents($markersFile, "fetch\n", FILE_APPEND | LOCK_EX);
                            usleep(250000);
                            return ['value' => 1];
                        }
                    );
                    exit($result === ['value' => 1] ? 0 : 1);
                }
                $children[] = $pid;
            }

            $deadline = microtime(true) + 5;
            while (count(glob($dir . '/ready-*') ?: []) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            touch($startFile);

            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $statuses[] = $status;
            }
            $children = [];

            $this->assertCount(4, glob($dir . '/ready-*') ?: []);
            foreach ($statuses as $status) {
                $this->assertTrue(pcntl_wifexited($status));
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            $this->assertCount(1, file($markersFile, FILE_IGNORE_NEW_LINES) ?: []);
        } finally {
            if (!is_file($startFile)) {
                touch($startFile);
            }
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($dir . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }
}
