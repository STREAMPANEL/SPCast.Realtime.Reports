<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/initializeAnalytics.php';

use App\AnalyticsService;
use App\ApiController;
use App\Logger;

$cacheDir = __DIR__ . '/cache/';
ensureSecureCacheDirectory($cacheDir);

$logger = Logger::createDefault();
$analyticsClient = initializeAnalytics();
$analyticsService = new AnalyticsService($analyticsClient, $logger);

$controller = new ApiController(
    $analyticsService,
    $logger,
    GA_PROPERTY_ID_INBOUND,
    GA_PROPERTY_ID_OUTBOUND,
    GA_PROPERTY_ID_GENERAL,
    $cacheDir,
    '__'
);

$response = $controller->handle($_GET, $_SERVER['REQUEST_METHOD'] ?? 'GET');

http_response_code($response['status']);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

echo json_encode($response['body'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

