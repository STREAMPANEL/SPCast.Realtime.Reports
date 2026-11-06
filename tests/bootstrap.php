<?php
declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';

    spl_autoload_register(static function (string $class): void {
        $prefix = 'Tests\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });

    if (!class_exists('Google_Service_AnalyticsData_RunRealtimeReportRequest')) {
        class Google_Service_AnalyticsData_RunRealtimeReportRequest
        {
            public mixed $config;

            public function __construct(mixed $config = null)
            {
                $this->config = $config;
            }
        }
    }

    if (!class_exists('Google_Service_AnalyticsData_RunReportRequest')) {
        class Google_Service_AnalyticsData_RunReportRequest
        {
            public mixed $config;

            public function __construct(mixed $config = null)
            {
                $this->config = $config;
            }
        }
    }
}

namespace Google\Service\AnalyticsData {
    if (!class_exists(__NAMESPACE__ . '\RunReportRequest')) {
        class RunReportRequest
        {
            public mixed $config;

            public function __construct(mixed $config = null)
            {
                $this->config = $config;
            }
        }
    }
}
