<?php
declare(strict_types=1);

function initializeAnalytics(mixed $client = null, bool $reset = false): ?\Google_Service_AnalyticsData
{
    static $analytics = null;

    if ($reset) {
        $analytics = null;
        if ($client === false) {
            return null;
        }
    }

    if ($analytics === null) {
        if ($client === null) {
            $client = new \Google_Client();
            $authConfig = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: '../service-account.json';
            if (is_string($authConfig) && is_file($authConfig) && is_readable($authConfig)) {
                try {
                    $client->setAuthConfig($authConfig);
                    $client->addScope(\Google_Service_AnalyticsData::ANALYTICS);
                } catch (\Throwable $e) {
                    if (class_exists(\App\Logger::class)) {
                        \App\Logger::createDefault()->error('Analytics auth initialization failed: ' . $e->getMessage());
                    }
                }
            } elseif (class_exists(\App\Logger::class)) {
                \App\Logger::createDefault()->error('Analytics credentials file not found or unreadable.');
            }
        }
        $analytics = new \Google_Service_AnalyticsData($client);
    }

    return $analytics;
}

function ensureSecureCacheDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $htaccess = rtrim($dir, '/') . '/.htaccess';
    if (!file_exists($htaccess)) {
        $rules = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\nOrder Allow,Deny\nDeny from all\n";
        file_put_contents($htaccess, $rules, LOCK_EX);
    }
}
