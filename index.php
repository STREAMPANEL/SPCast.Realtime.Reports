<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\ReportType;
use App\PublicReportFilter;
use App\SecurityHeaders;
use App\SeoHelper;

$cspNonce = SecurityHeaders::sendHtml();

$requestedStat = isset($_GET['stat']) && is_string($_GET['stat'])
    ? ReportType::tryFrom(strtolower(trim($_GET['stat'])))
    : null;
$initialType = $requestedStat ?? ReportType::Country;

$allowedPeriods = ['today', 'yesterday', '7days', '30days', 'realtime', 'minutes', 'weekly', 'monthly'];
$requestedPeriod = isset($_GET['period']) && is_string($_GET['period']) && in_array($_GET['period'], $allowedPeriods, true)
    ? $_GET['period']
    : ($initialType->isInOut() ? 'realtime' : 'today');

// Read cached realtime counts if available
$warmInbound = 0;
$warmOutbound = 0;
$inCachePath = __DIR__ . '/cache/analytics_cache_in.json';
$outCachePath = __DIR__ . '/cache/analytics_cache_out.json';
if (is_file($inCachePath)) {
    $decodedIn = json_decode((string) file_get_contents($inCachePath), true);
    if (is_array($decodedIn) && isset($decodedIn['activeUsers'])) {
        $warmInbound = (int) $decodedIn['activeUsers'];
    }
}
if (is_file($outCachePath)) {
    $decodedOut = json_decode((string) file_get_contents($outCachePath), true);
    if (is_array($decodedOut) && isset($decodedOut['activeUsers'])) {
        $warmOutbound = (int) $decodedOut['activeUsers'];
    }
}
$warmNet = $warmInbound - $warmOutbound;

$warmHubEntries = [];
$warmHubTotal = 0;
$warmHubCacheFile = '';
$warmAggregateLabel = PublicReportFilter::usesAggregation($initialType) ? __('Other') : null;
if (!$initialType->isInOut() && in_array($requestedPeriod, ['today', 'yesterday', '7days', '30days'], true)) {
    $warmHubCacheFile = __DIR__ . '/cache/analytics_' . $initialType->value . '_cache_' . $requestedPeriod . '.json';
    if (is_file($warmHubCacheFile)) {
        $decodedHub = json_decode((string) file_get_contents($warmHubCacheFile), true);
        if (is_array($decodedHub) && $decodedHub !== []) {
            $publicHubData = PublicReportFilter::filterPeriod($initialType, $decodedHub, $warmAggregateLabel ?? '');
            $warmHubEntries = SeoHelper::normalizeMapEntries($publicHubData, $warmAggregateLabel);
            foreach ($warmHubEntries as $hubEntry) {
                $warmHubTotal += $hubEntry['count'];
            }
        }
    }
}

$activeLang = (string) ($current_lang ?? 'de');
$pageTitle = 'SPCast ' . __('Realtime Statistics') . ' - SPCast Live';
$pageDescription = __('Get realtime statistics on user activities in the SPCast network. Insights into incoming and outgoing data, updated live.');
$heroDescription = __('Get anonymized realtime statistics on user activities in the SPCast network. Track general trends and analyze the user behavior of all radio stations in a centralized overview.');
$pageKeywords = __('spcast live, spcast echtzeitstatistiken, nutzerstatistik, netzwerkstatistiken, live daten, sendernutzung');
$baseCanonicalUrl = 'https://live.spcast.eu/';
$canonicalUrl = SeoHelper::buildCanonicalUrl($baseCanonicalUrl, $activeLang);
$seoRobots = SeoHelper::resolveRobotsDirective($_GET);
$schemaGraph = SeoHelper::buildHomeSchemaGraph(
    $canonicalUrl,
    $activeLang,
    $pageTitle,
    $pageDescription,
    $heroDescription,
    $pageKeywords,
    '__',
    [$inCachePath, $outCachePath, $warmHubCacheFile]
);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($activeLang, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php require_once __DIR__ . '/includes/head.php'; ?>
</head>

<body>
    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="container py-4 py-lg-5">
        <section class="sp-hero text-center">
            <div class="mb-2">
                <span class="sp-live-badge">
                    <span class="sp-live-dot" aria-hidden="true"></span>
                    <span><?php echo htmlspecialchars(__('Live Network Telemetry'), ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
            </div>
            <h1 class="display-6 fw-bold mb-3">SPCast <?php echo htmlspecialchars(__('Realtime Statistics'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="sp-hero-lead mb-0">
                <?php echo htmlspecialchars(__('Get anonymized realtime statistics on user activities in the SPCast network. Track general trends and analyze the user behavior of all radio stations in a centralized overview.'), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </section>

        <section class="row g-3 mb-4" aria-label="<?php echo htmlspecialchars(__('Realtime Overview'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="col-sm-6 col-xl-3">
                <div class="sp-kpi-card">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars(__('Incoming'), ENT_QUOTES, 'UTF-8'); ?> (30m)</span>
                        <i class="bi bi-box-arrow-in-right" style="color: var(--sp-accent);" aria-hidden="true"></i>
                    </div>
                    <div class="sp-kpi-value" id="pulseInbound"><?php echo (int) $warmInbound; ?></div>
                    <div class="sp-kpi-sub d-flex justify-content-between align-items-center">
                        <span><?php echo htmlspecialchars(__('Tuned in (last 30 min)'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <a href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl('in.php', $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none fw-semibold"><?php echo htmlspecialchars(__('Details'), ENT_QUOTES, 'UTF-8'); ?> &rarr;</a>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sp-kpi-card">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars(__('Outgoing'), ENT_QUOTES, 'UTF-8'); ?> (30m)</span>
                        <i class="bi bi-box-arrow-right" style="color: var(--sp-accent-secondary);" aria-hidden="true"></i>
                    </div>
                    <div class="sp-kpi-value" id="pulseOutbound"><?php echo (int) $warmOutbound; ?></div>
                    <div class="sp-kpi-sub d-flex justify-content-between align-items-center">
                        <span><?php echo htmlspecialchars(__('Tuned out (last 30 min)'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <a href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl('out.php', $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none fw-semibold"><?php echo htmlspecialchars(__('Details'), ENT_QUOTES, 'UTF-8'); ?> &rarr;</a>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sp-kpi-card">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars(__('Net Stream Flow'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="bi bi-activity" style="color: var(--sp-accent);" aria-hidden="true"></i>
                    </div>
                    <div class="sp-kpi-value" id="pulseNetFlow"><?php echo ($warmNet > 0 ? '+' : '') . (int) $warmNet; ?></div>
                    <div class="sp-kpi-sub">
                        <span><?php echo htmlspecialchars(__('Balance of incoming vs. outgoing'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sp-kpi-card">
                    <div class="sp-kpi-label">
                        <span><?php echo htmlspecialchars(__('Auto Refresh'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 my-1">
                        <span class="sp-kpi-value my-0" id="countdown">45</span>
                        <span class="sp-kpi-sub" id="countdownUnit"><?php echo htmlspecialchars(__('seconds'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="d-flex gap-2 mt-1">
                        <button type="button" id="spManualRefreshBtn" class="sp-btn-ghost py-1 px-2 flex-grow-1" aria-label="<?php echo htmlspecialchars(__('Refresh now'), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars(__('Refresh now'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                        <button type="button" id="spPauseRefreshBtn" class="sp-btn-ghost py-1 px-2" aria-pressed="false" aria-label="<?php echo htmlspecialchars(__('Pause auto-refresh'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Pause auto-refresh'), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-pause-fill" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="sp-card sp-hub-shell mb-5" id="spStatsHub"
                 data-initial-type="<?php echo htmlspecialchars($initialType->value, ENT_QUOTES, 'UTF-8'); ?>"
                 data-initial-period="<?php echo htmlspecialchars($requestedPeriod, ENT_QUOTES, 'UTF-8'); ?>"
                 aria-labelledby="hubHeading">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 pb-3 border-bottom" style="border-color: var(--sp-border) !important;">
                <div>
                    <h2 class="h4 mb-1" id="hubHeading"><?php echo htmlspecialchars(__('Interactive Statistics Hub'), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="mb-0 small" style="color: var(--sp-muted);" id="hubCategoryDesc">
                        <?php echo htmlspecialchars(__($initialType->descriptionMsgId()), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <a id="hubDetailLink" href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl($initialType->pageUrl(), $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="sp-btn-primary">
                        <span id="hubDetailLabel"><?php echo htmlspecialchars(__('Open dedicated view'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="sp-pill-rail mb-3" role="tablist" aria-label="<?php echo htmlspecialchars(__('Select statistic category'), ENT_QUOTES, 'UTF-8'); ?>">
                <?php foreach (ReportType::cases() as $case):
                    $isInitial = ($case === $initialType);
                ?>
                    <button type="button"
                            role="tab"
                            class="sp-pill-btn<?php echo $isInitial ? ' active' : ''; ?>"
                            data-hub-type="<?php echo htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8'); ?>"
                            data-page-url="<?php echo htmlspecialchars($case->pageUrl(), ENT_QUOTES, 'UTF-8'); ?>"
                            aria-selected="<?php echo $isInitial ? 'true' : 'false'; ?>">
                        <i class="bi <?php echo htmlspecialchars($case->iconClass(), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars(__($case->labelMsgId()), ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-3">
                <div class="d-flex flex-wrap gap-2" id="hubPeriodGroup" role="group" aria-label="<?php echo htmlspecialchars(__('Select time period'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="button" class="sp-btn-ghost active" data-hub-period="today"><?php echo htmlspecialchars(__('Today'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="button" class="sp-btn-ghost" data-hub-period="yesterday"><?php echo htmlspecialchars(__('Yesterday'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="button" class="sp-btn-ghost" data-hub-period="7days"><?php echo htmlspecialchars(__('Last 7 Days'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="button" class="sp-btn-ghost" data-hub-period="30days"><?php echo htmlspecialchars(__('Last 30 Days'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2" id="hubDimensionControls">
                    <div class="d-inline-flex gap-1" role="group" aria-label="<?php echo htmlspecialchars(__('Chart scale'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" class="sp-btn-ghost" data-scale-mode="linear" aria-pressed="false"><?php echo htmlspecialchars(__('Linear'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="button" class="sp-btn-ghost" data-scale-mode="logarithmic" aria-pressed="true"><?php echo htmlspecialchars(__('Logarithmic'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    <div class="d-inline-flex gap-1" role="group" aria-label="<?php echo htmlspecialchars(__('Chart limit'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" class="sp-btn-ghost" data-limit-mode="15" aria-pressed="true"><?php echo htmlspecialchars(__('Top 15'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="button" class="sp-btn-ghost" data-limit-mode="0" aria-pressed="false"><?php echo htmlspecialchars(__('All entries'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    <div>
                        <label for="hubTableSearch" class="visually-hidden"><?php echo htmlspecialchars(__('Filter entries'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="search" id="hubTableSearch" class="sp-input" placeholder="<?php echo htmlspecialchars(__('Filter entries...'), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3" id="hubSummaryRow">
                <div class="col-sm-4">
                    <div class="p-2 px-3 rounded-3 border" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                        <div class="small" style="color: var(--sp-muted);" id="hubStat1Label"><?php echo htmlspecialchars(__('Total events'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="fw-bold fs-5" style="color: var(--sp-heading);" id="hubStat1Value"><?php echo $warmHubEntries !== [] ? number_format($warmHubTotal, 0, '.', ',') : '-'; ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="p-2 px-3 rounded-3 border" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                        <div class="small" style="color: var(--sp-muted);" id="hubStat2Label"><?php echo htmlspecialchars(__('Unique entries'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="fw-bold fs-5" style="color: var(--sp-heading);" id="hubStat2Value"><?php echo $warmHubEntries !== [] ? number_format(count($warmHubEntries), 0, '.', ',') : '-'; ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="p-2 px-3 rounded-3 border" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                        <div class="small" style="color: var(--sp-muted);" id="hubStat3Label"><?php echo htmlspecialchars(__('Top entry'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="fw-bold fs-5 text-truncate" style="color: var(--sp-accent);" id="hubStat3Value"><?php echo $warmHubEntries !== [] ? htmlspecialchars($warmHubEntries[0]['label'] . ' (' . number_format($warmHubEntries[0]['count'], 0, '.', ',') . ')', ENT_QUOTES, 'UTF-8') : '-'; ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <div class="sp-chart-box h-100 d-flex flex-column justify-content-center">
                        <div class="sp-loading-overlay d-none" id="hubLoadingOverlay" role="status" aria-live="polite">
                            <span class="fw-semibold"><?php echo htmlspecialchars(__('Loading statistics...'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div id="hubChartEmpty" class="sp-empty-state d-none">
                            <i class="bi bi-bar-chart-line fs-2 mb-2" aria-hidden="true"></i>
                            <p class="mb-0" id="hubEmptyMessage"><?php echo htmlspecialchars(__('No statistics available for the selected period yet.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <canvas id="hubMainChart"></canvas>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div tabindex="0" role="region" aria-labelledby="hubTableCaption" class="sp-table-wrapper h-100">
                        <table class="sp-ranking-table" id="hubRankingTable"
                               style="--label-2: '<?php echo htmlspecialchars(addcslashes(__('Count'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>'; --label-3: '<?php echo htmlspecialchars(addcslashes(__('Share'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>';">
                            <caption id="hubTableCaption"><?php echo htmlspecialchars(__('Detailed breakdown for the selected statistic and period'), ENT_QUOTES, 'UTF-8'); ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col" id="hubCol1Header"><?php echo htmlspecialchars(__($initialType->labelMsgId()), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th scope="col" class="text-end" id="hubCol2Header"><?php echo htmlspecialchars(__('Count'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th scope="col"><?php echo htmlspecialchars(__('Share'), ENT_QUOTES, 'UTF-8'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="hubRankingBody"><?php echo SeoHelper::renderRankingRowsHtml($warmHubEntries, $warmHubTotal, __('Loading statistics...')); ?></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="allViewsHeading">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h2 class="h4 mb-1" id="allViewsHeading"><?php echo htmlspecialchars(__('All Individual Views'), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="mb-0 small" style="color: var(--sp-muted);">
                        <?php echo htmlspecialchars(__('Direct access to all specialized realtime and historical report pages.'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <?php foreach (ReportType::streamCases() as $streamCase): ?>
                    <div class="col-md-6">
                        <div class="sp-card sp-card-interactive sp-card-accent h-100 p-4 d-flex flex-column">
                            <div class="sp-card-icon">
                                <i class="bi <?php echo htmlspecialchars($streamCase->iconClass(), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            </div>
                            <h3 class="h5 mb-2"><?php echo htmlspecialchars(__($streamCase->labelMsgId()), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="mb-4 flex-grow-1" style="color: var(--sp-muted);">
                                <?php echo htmlspecialchars(__($streamCase->descriptionMsgId()), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <div>
                                <a href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl($streamCase->pageUrl(), $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="sp-btn-primary">
                                    <span><?php echo htmlspecialchars(__('View'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="visually-hidden"> <?php echo htmlspecialchars(__($streamCase->labelMsgId()), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <?php foreach (ReportType::dimensionCases() as $dimCase): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="sp-card sp-card-interactive h-100 p-4 d-flex flex-column">
                            <div class="sp-card-icon">
                                <i class="bi <?php echo htmlspecialchars($dimCase->iconClass(), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            </div>
                            <h3 class="h5 mb-2"><?php echo htmlspecialchars(__($dimCase->labelMsgId()), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="mb-4 flex-grow-1" style="color: var(--sp-muted);">
                                <?php echo htmlspecialchars(__($dimCase->descriptionMsgId()), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <div>
                                <a href="<?php echo htmlspecialchars(SeoHelper::localizeRelativeUrl($dimCase->pageUrl(), $activeLang), ENT_QUOTES, 'UTF-8'); ?>" class="sp-btn-ghost">
                                    <span><?php echo htmlspecialchars(__('View'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="visually-hidden"> <?php echo htmlspecialchars(__($dimCase->labelMsgId()), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="mt-5">
            <div class="sp-card p-4">
                <h2 class="h5 mb-1"><?php echo htmlspecialchars(__('Methodology & Data Transparency'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="sp-methodology-lead mb-3">
                    <?php echo htmlspecialchars(__('Technical notes on how telemetry metrics on SPCast Live are collected, aggregated, and published.'), ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="sp-methodology-item">
                            <h3 class="sp-methodology-item-title">
                                <i class="bi bi-hdd-network" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars(__('Data Source & Interval'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </h3>
                            <p class="sp-methodology-item-text">
                                <?php echo SeoHelper::renderInterpolatedLink(
                                    __('Aggregated listener and stream events across the %s audio streaming infrastructure (Icecast, SHOUTcast, HLS), refreshed every 45 seconds with a rolling 30-day history.'),
                                    SeoHelper::BRAND_URL,
                                    'SPCast'
                                ); ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="sp-methodology-item">
                            <h3 class="sp-methodology-item-title">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars(__('Anonymization & Privacy'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </h3>
                            <p class="sp-methodology-item-text">
                                <?php echo htmlspecialchars(__('City and mountpoint groups with fewer than five events are combined. Listener IP addresses are not published.'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="sp-methodology-item">
                            <h3 class="sp-methodology-item-title">
                                <i class="bi bi-code-square" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars(__('Open-Source Dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </h3>
                            <p class="sp-methodology-item-text">
                                <?php echo SeoHelper::renderInterpolatedLink(
                                    __('The reporting frontend, caching layer, and schema definitions are publicly auditable on %s under the EUPL-1.2 license.'),
                                    'https://github.com/STREAMPANEL/SPCast.Realtime.Reports',
                                    'GitHub'
                                ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>

</html>
