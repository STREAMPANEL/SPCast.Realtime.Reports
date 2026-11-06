<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80500) {
    throw new \RuntimeException('SPCast Realtime Reports requires PHP 8.5 or newer.');
}

if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// GA4 property IDs (environment variables override defaults)
if (!defined('GA_PROPERTY_ID_OUTBOUND')) {
    define('GA_PROPERTY_ID_OUTBOUND', getenv('GA_PROPERTY_ID_OUTBOUND') ?: 'properties/457889788');
}
if (!defined('GA_PROPERTY_ID_INBOUND')) {
    define('GA_PROPERTY_ID_INBOUND', getenv('GA_PROPERTY_ID_INBOUND') ?: 'properties/457854648');
}
if (!defined('GA_PROPERTY_ID_GENERAL')) {
    define('GA_PROPERTY_ID_GENERAL', getenv('GA_PROPERTY_ID_GENERAL') ?: 'properties/453756722');
}
