<?php

declare(strict_types=1);

function initializeAnalytics($client = null, bool $reset = false)
{
    static $analytics = null;

    if ($reset) {
        $analytics = null;
        // If we are just resetting and not providing a client, we might want to return early or proceed to re-initialize.
        // If $client is explicitly false (not null), we can use that as a signal to just clear and return.
        if ($client === false) {
            return null;
        }
    }

    if ($analytics === null) {
        if ($client === null) {
            $client = new Google_Client();
            $authConfig = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: '../service-account.json';
            $client->setAuthConfig($authConfig);
            $client->addScope(Google_Service_AnalyticsData::ANALYTICS);
        }
        $analytics = new Google_Service_AnalyticsData($client);
    }

    return $analytics;
}

function ensureSecureCacheDirectory($dir)
{
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $htaccess = $dir . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order Allow,Deny\nDeny from all");
    }
}
