<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/initializeAnalytics.php';

use App\AnalyticsService;
use App\Logger;
use App\ReportType;
use App\SecurityHeaders;
use App\SeoHelper;

$cspNonce = SecurityHeaders::sendHtml();

$cacheDir = __DIR__ . '/../cache/';
ensureSecureCacheDirectory($cacheDir);

$type = (string) ($inoutConfig['type'] ?? 'in');
$gaPropertyId = (string) ($inoutConfig['gaPropertyId'] ?? GA_PROPERTY_ID_INBOUND);
$reportEnum = ReportType::tryFrom($type);
$iconClass = $reportEnum !== null ? $reportEnum->iconClass() : 'bi-activity';

$logger = Logger::createDefault();
$analyticsClient = initializeAnalytics();
$analyticsService = new AnalyticsService($analyticsClient, $logger);

$inOutData = $analyticsService->getInOutReportData($type, $gaPropertyId, $cacheDir, 45);
$activeUsers = $inOutData['activeUsers'];
$peakToday = $inOutData['hourly']['values'] !== [] ? max($inOutData['hourly']['values']) : 0;
$sumWeekly = array_sum($inOutData['weekly']['values']);
$sumHourly = array_sum($inOutData['hourly']['values']);
$hourlyEntries = SeoHelper::normalizeSeriesEntries($inOutData['hourly']['labels'], $inOutData['hourly']['values']);

$activeLang = (string) ($current_lang ?? 'de');
$baseCanonicalUrl = (string) $inoutConfig['canonicalUrl'];
$canonicalUrl = SeoHelper::buildCanonicalUrl($baseCanonicalUrl, $activeLang);
$schemaGraph = SeoHelper::buildReportSchemaGraph(
    $reportEnum ?? ReportType::In,
    $canonicalUrl,
    $activeLang,
    (string) $inoutConfig['pageTitle'],
    (string) $inoutConfig['headerTitle'],
    (string) $inoutConfig['pageDescription'],
    (string) $inoutConfig['headerDesc'],
    (string) $inoutConfig['keywords'],
    (string) $inoutConfig['cardTitle'],
    (int) $activeUsers,
    [
        $cacheDir . "analytics_cache_{$type}.json",
        $cacheDir . "analytics_monthly_{$type}.json",
    ]
);

$initialInOutPayload = [
    'type' => $type,
    'activeUsers' => $activeUsers,
    'periods' => [
        'realtime' => [
            'label' => __('Today'),
            'datasetLabel' => (string) $inoutConfig['chartLabels']['realtime'],
            'xAxisLabel' => __('Hour of the day'),
            'yAxisLabel' => (string) $inoutConfig['chartLabels']['realtimeY'],
            'labels' => $inOutData['hourly']['labels'],
            'values' => $inOutData['hourly']['values'],
        ],
        'minutes' => [
            'label' => __('Last 30 Minutes'),
            'datasetLabel' => (string) $inoutConfig['chartLabels']['realtime'],
            'xAxisLabel' => __('Minutes ago'),
            'yAxisLabel' => __('Users'),
            'labels' => $inOutData['minutes']['labels'],
            'values' => $inOutData['minutes']['values'],
        ],
        'weekly' => [
            'label' => __('Last 7 Days'),
            'datasetLabel' => (string) $inoutConfig['chartLabels']['weekly'],
            'xAxisLabel' => __('Date'),
            'yAxisLabel' => __('Users'),
            'labels' => $inOutData['weekly']['labels'],
            'values' => $inOutData['weekly']['values'],
        ],
        'monthly' => [
            'label' => __('Last 30 Days'),
            'datasetLabel' => (string) $inoutConfig['chartLabels']['monthly'],
            'xAxisLabel' => __('Date'),
            'yAxisLabel' => __('Users'),
            'labels' => $inOutData['monthly']['labels'],
            'values' => $inOutData['monthly']['values'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($activeLang, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) $inoutConfig['pageTitle'], ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars((string) $inoutConfig['pageDescription'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars((string) $inoutConfig['keywords'], ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php require_once __DIR__ . '/head.php'; ?>
</head>

<body>
    <?php require_once __DIR__ . '/nav.php'; ?>

    <main class="container py-4 py-lg-5" id="spStandaloneInOut" data-inout-type="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <a href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl('./?stat=' . rawurlencode($type), $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="sp-btn-ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars(__('Back to Statistics Hub'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="small" style="color: var(--sp-muted);">
                    <?php echo htmlspecialchars(__('Next update in'), ENT_QUOTES, 'UTF-8'); ?>
                    <strong style="color: var(--sp-heading);" id="countdown">45</strong>
                    <span id="countdownUnit"><?php echo htmlspecialchars(__('seconds'), ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <button type="button" id="spManualRefreshBtn" class="sp-btn-ghost py-1 px-2" aria-label="<?php echo htmlspecialchars(__('Refresh now'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Refresh now'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                </button>
                <button type="button" id="spPauseRefreshBtn" class="sp-btn-ghost py-1 px-2" aria-pressed="false" aria-label="<?php echo htmlspecialchars(__('Pause auto-refresh'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Pause auto-refresh'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-pause-fill" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <section class="sp-hero pt-2 text-center">
            <div class="sp-card-icon mx-auto">
                <i class="bi <?php echo htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
            </div>
            <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars((string) $inoutConfig['headerTitle'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="sp-hero-lead mb-0"><?php echo htmlspecialchars((string) $inoutConfig['headerDesc'], ENT_QUOTES, 'UTF-8'); ?></p>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="sp-kpi-card sp-card-accent">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars((string) $inoutConfig['cardTitle'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="sp-live-dot" aria-hidden="true"></span>
                    </div>
                    <div class="sp-kpi-value" id="activeUsers"><?php echo (int) $activeUsers; ?></div>
                    <div class="sp-kpi-sub"><?php echo htmlspecialchars((string) $inoutConfig['cardDesc'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sp-kpi-card">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars(__('Peak Today'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="bi bi-graph-up" aria-hidden="true"></i>
                    </div>
                    <div class="sp-kpi-value" id="inoutPeakToday"><?php echo (int) $peakToday; ?></div>
                    <div class="sp-kpi-sub"><?php echo htmlspecialchars(__('Highest hourly value recorded today'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sp-kpi-card">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars(__('Last 7 Days Total'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                    </div>
                    <div class="sp-kpi-value" id="inoutWeeklyTotal"><?php echo (int) $sumWeekly; ?></div>
                    <div class="sp-kpi-sub"><?php echo htmlspecialchars(__('Cumulative active users (7 days)'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </section>

        <section class="sp-card sp-hub-shell">
            <ul class="nav nav-tabs sp-tabs flex-nowrap overflow-auto mb-3" id="inoutTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-inout-period="realtime" type="button" role="tab" aria-selected="true">
                        <?php echo htmlspecialchars(__('Today'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-inout-period="minutes" type="button" role="tab" aria-selected="false">
                        <?php echo htmlspecialchars(__('Last 30 Minutes'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-inout-period="weekly" type="button" role="tab" aria-selected="false">
                        <?php echo htmlspecialchars(__('Last 7 Days'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-inout-period="monthly" type="button" role="tab" aria-selected="false">
                        <?php echo htmlspecialchars(__('Last 30 Days'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </li>
            </ul>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-8">
                    <div class="sp-chart-box h-100 d-flex flex-column justify-content-center">
                        <canvas id="standaloneInOutChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div tabindex="0" role="region" aria-labelledby="inoutTableCaption" class="sp-table-wrapper h-100">
                        <table class="sp-ranking-table"
                               style="--label-2: '<?php echo htmlspecialchars(addcslashes(__('Users'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>'; --label-3: '<?php echo htmlspecialchars(addcslashes(__('Share'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>';">
                            <caption id="inoutTableCaption"><?php echo htmlspecialchars((string) $inoutConfig['headerTitle'], ENT_QUOTES, 'UTF-8'); ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col" id="inoutTimeColHeader"><?php echo htmlspecialchars(__('Hour of the day'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th scope="col" class="text-end"><?php echo htmlspecialchars(__('Users'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th scope="col"><?php echo htmlspecialchars(__('Share'), ENT_QUOTES, 'UTF-8'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="standaloneInOutBody"><?php
                                echo SeoHelper::renderRankingRowsHtml(
                                    $hourlyEntries,
                                    (int) $sumHourly,
                                    __('No statistics available for the selected period yet.')
                                );
                            ?></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" id="spcast-inout-payload" type="application/json"><?php echo json_encode($initialInOutPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

    <?php require_once __DIR__ . '/footer.php'; ?>
</body>

</html>
