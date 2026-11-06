<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/initializeAnalytics.php';

use App\AnalyticsService;
use App\Logger;
use App\PublicReportFilter;
use App\ReportType;
use App\SecurityHeaders;
use App\SeoHelper;

$cspNonce = SecurityHeaders::sendHtml();

$cacheDir = __DIR__ . '/../cache/';
ensureSecureCacheDirectory($cacheDir);

$logger = Logger::createDefault();
$analyticsClient = initializeAnalytics();
$analyticsService = new AnalyticsService($analyticsClient, $logger);

$typeId = (string) ($reportConfig['typeId'] ?? 'country');
$propertyId = (string) ($reportConfig['gaPropertyId'] ?? GA_PROPERTY_ID_GENERAL);
$reportEnum = ReportType::tryFrom($typeId);
$defaultScale = $reportEnum !== null ? $reportEnum->defaultScale() : 'logarithmic';
$iconClass = $reportEnum !== null ? $reportEnum->iconClass() : 'bi-bar-chart-line';

$reportData = $analyticsService->getStandardReportData($typeId, $propertyId, 45, $cacheDir);
$aggregateLabel = $reportEnum !== null && PublicReportFilter::usesAggregation($reportEnum) ? __('Other') : null;
$reportData = PublicReportFilter::filterPeriods($reportEnum ?? ReportType::Country, $reportData, $aggregateLabel ?? '');

$tabPeriods = [
    'today' => [
        'id' => 'today',
        'label' => __('Today'),
        'title' => (string) ($reportConfig['chartTitles']['today'] ?? __('Today')),
        'data' => $reportData['today'] ?? [],
    ],
    'yesterday' => [
        'id' => 'yesterday',
        'label' => __('Yesterday'),
        'title' => (string) ($reportConfig['chartTitles']['yesterday'] ?? __('Yesterday')),
        'data' => $reportData['yesterday'] ?? [],
    ],
    '7days' => [
        'id' => '7days',
        'label' => __('Last 7 Days'),
        'title' => (string) ($reportConfig['chartTitles']['7days'] ?? __('Last 7 Days')),
        'data' => $reportData['7days'] ?? [],
    ],
    '30days' => [
        'id' => '30days',
        'label' => __('Last 30 Days'),
        'title' => (string) ($reportConfig['chartTitles']['30days'] ?? __('Last 30 Days')),
        'data' => $reportData['30days'] ?? [],
    ],
];

$todaySummary = $analyticsService->summarizePeriodRanking($tabPeriods['today']['data']);
$todayEntries = SeoHelper::normalizeMapEntries($tabPeriods['today']['data'], $aggregateLabel);

$activeLang = (string) ($current_lang ?? 'de');
$baseCanonicalUrl = (string) $reportConfig['canonicalUrl'];
$canonicalUrl = SeoHelper::buildCanonicalUrl($baseCanonicalUrl, $activeLang);
$schemaGraph = SeoHelper::buildReportSchemaGraph(
    $reportEnum ?? ReportType::Country,
    $canonicalUrl,
    $activeLang,
    (string) $reportConfig['pageTitle'],
    (string) $reportConfig['headerTitle'],
    (string) $reportConfig['pageDescription'],
    (string) $reportConfig['headerDesc'],
    (string) $reportConfig['keywords'],
    (string) ($reportConfig['chartLabel'] ?? __('Entry')) . ' (' . __('Today') . ')',
    (int) $todaySummary['totalEvents'],
    [
        $cacheDir . "analytics_{$typeId}_cache_today.json",
        $cacheDir . "analytics_{$typeId}_cache_30days.json",
    ]
);

$initialPayload = [
    'type' => $typeId,
    'axisLabel' => (string) ($reportConfig['chartLabel'] ?? ''),
    'supportsDetailModal' => $reportEnum !== null ? $reportEnum->supportsDetailModal() : true,
    'defaultScale' => $defaultScale,
    'aggregateLabel' => $aggregateLabel,
    'periodLabels' => [
        'today' => $tabPeriods['today']['label'],
        'yesterday' => $tabPeriods['yesterday']['label'],
        '7days' => $tabPeriods['7days']['label'],
        '30days' => $tabPeriods['30days']['label'],
    ],
    'chartTitles' => [
        'today' => $tabPeriods['today']['title'],
        'yesterday' => $tabPeriods['yesterday']['title'],
        '7days' => $tabPeriods['7days']['title'],
        '30days' => $tabPeriods['30days']['title'],
    ],
    'periods' => [
        'today' => $tabPeriods['today']['data'],
        'yesterday' => $tabPeriods['yesterday']['data'],
        '7days' => $tabPeriods['7days']['data'],
        '30days' => $tabPeriods['30days']['data'],
    ],
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($activeLang, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) $reportConfig['pageTitle'], ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars((string) $reportConfig['pageDescription'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars((string) $reportConfig['keywords'], ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php require_once __DIR__ . '/head.php'; ?>
</head>

<body>
    <?php require_once __DIR__ . '/nav.php'; ?>

    <main class="container py-4 py-lg-5" id="spStandaloneReport" data-report-type="<?php echo htmlspecialchars($typeId, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <a href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl('./?stat=' . rawurlencode($typeId), $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="sp-btn-ghost">
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
            <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars((string) $reportConfig['headerTitle'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="sp-hero-lead mb-0"><?php echo htmlspecialchars((string) $reportConfig['headerDesc'], ENT_QUOTES, 'UTF-8'); ?></p>
        </section>

        <section class="sp-card sp-hub-shell">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <h2 class="h5 mb-0" id="standaloneChartTitle"><?php echo htmlspecialchars($tabPeriods['today']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-inline-flex gap-1" role="group" aria-label="<?php echo htmlspecialchars(__('Chart scale'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" class="sp-btn-ghost" data-scale-mode="linear" aria-pressed="<?php echo $defaultScale === 'linear' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars(__('Linear'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="button" class="sp-btn-ghost" data-scale-mode="logarithmic" aria-pressed="<?php echo $defaultScale === 'logarithmic' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars(__('Logarithmic'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    <div class="d-inline-flex gap-1" role="group" aria-label="<?php echo htmlspecialchars(__('Chart limit'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" class="sp-btn-ghost" data-limit-mode="15" aria-pressed="true"><?php echo htmlspecialchars(__('Top 15'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="button" class="sp-btn-ghost" data-limit-mode="0" aria-pressed="false"><?php echo htmlspecialchars(__('All entries'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    <div>
                        <label for="standaloneTableSearch" class="visually-hidden"><?php echo htmlspecialchars(__('Filter entries'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="search" id="standaloneTableSearch" class="sp-input" placeholder="<?php echo htmlspecialchars(__('Filter entries...'), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs sp-tabs flex-nowrap overflow-auto mb-3" id="reportTabs" role="tablist">
                <?php foreach ($tabPeriods as $key => $tab):
                    $isActive = ($key === 'today');
                ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                                id="tab-<?php echo htmlspecialchars($tab['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-report-period="<?php echo htmlspecialchars($tab['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                type="button" role="tab"
                                aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>">
                            <span><?php echo htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="sp-badge-count ms-1" data-period-badge="<?php echo htmlspecialchars($tab['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo count($tab['data']); ?></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="row g-2 mb-3">
                <div class="col-sm-4">
                    <div class="p-2 px-3 rounded-3 border" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                        <div class="small" style="color: var(--sp-muted);"><?php echo htmlspecialchars(__('Total events'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="fw-bold fs-5" style="color: var(--sp-heading);" id="reportStatTotal"><?php echo (int) $todaySummary['totalEvents']; ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="p-2 px-3 rounded-3 border" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                        <div class="small" style="color: var(--sp-muted);"><?php echo htmlspecialchars(__('Unique entries'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="fw-bold fs-5" style="color: var(--sp-heading);" id="reportStatUnique"><?php echo (int) $todaySummary['uniqueCount']; ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="p-2 px-3 rounded-3 border" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                        <div class="small" style="color: var(--sp-muted);"><?php echo htmlspecialchars(__('Top entry'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="fw-bold fs-5 text-truncate" style="color: var(--sp-accent);" id="reportStatTop">
                            <?php echo $todaySummary['topItem'] !== null ? htmlspecialchars($todaySummary['topItem']['label'] . ' (' . $todaySummary['topItem']['count'] . ')', ENT_QUOTES, 'UTF-8') : '-'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <div class="sp-chart-box h-100 d-flex flex-column justify-content-center">
                        <div id="standaloneChartEmpty" class="sp-empty-state d-none">
                            <i class="bi bi-bar-chart-line fs-2 mb-2" aria-hidden="true"></i>
                            <p class="mb-0"><?php echo htmlspecialchars(__('No statistics available for the selected period yet.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <canvas id="standaloneReportChart"></canvas>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div tabindex="0" role="region" aria-labelledby="standaloneTableCaption" class="sp-table-wrapper h-100">
                        <table class="sp-ranking-table"
                               style="--label-2: '<?php echo htmlspecialchars(addcslashes(__('Count'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>'; --label-3: '<?php echo htmlspecialchars(addcslashes(__('Share'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>';">
                            <caption id="standaloneTableCaption"><?php echo htmlspecialchars((string) $reportConfig['sectionTitle'], ENT_QUOTES, 'UTF-8'); ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo htmlspecialchars((string) $reportConfig['chartLabel'], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th scope="col" class="text-end"><?php echo htmlspecialchars(__('Count'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th scope="col"><?php echo htmlspecialchars(__('Share'), ENT_QUOTES, 'UTF-8'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="standaloneRankingBody"><?php
                                echo SeoHelper::renderRankingRowsHtml(
                                    $todayEntries,
                                    (int) $todaySummary['totalEvents'],
                                    __('No statistics available for the selected period yet.')
                                );
                            ?></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" id="spcast-report-payload" type="application/json"><?php echo json_encode($initialPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

    <?php require_once __DIR__ . '/footer.php'; ?>
</body>

</html>
