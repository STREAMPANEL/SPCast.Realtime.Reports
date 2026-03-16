<?php

declare(strict_types=1);

// includes/report_template.php
require_once 'includes/i18n.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir = 'cache/';
require_once 'includes/initializeAnalytics.php';
ensureSecureCacheDirectory($cacheDir);
require_once 'includes/config.php';

$logger = \App\Logger::createDefault();
$analyticsClient = initializeAnalytics();
$analyticsService = new \App\AnalyticsService($analyticsClient, $logger);

$reportData = $analyticsService->getStandardReportData($reportConfig['typeId'], GA_PROPERTY_ID_GENERAL);
$resultsToday = $reportData['today'];
$resultsYesterday = $reportData['yesterday'];
$results7Days = $reportData['7days'];
$results30Days = $reportData['30days'];
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang ?? 'de'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $reportConfig['pageTitle']; ?></title>
    <meta name="description" content="<?php echo $reportConfig['pageDescription']; ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($reportConfig['keywords'], ENT_QUOTES); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($reportConfig['canonicalUrl'], ENT_QUOTES); ?>" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center"><?php echo $reportConfig['headerTitle']; ?></h1>
            <p><?php echo $reportConfig['headerDesc']; ?></p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $reportConfig['sectionTitle']; ?></h3>
                        <p><?php echo __('Next update in'); ?> <span id="countdown">45</span> <?php echo __('seconds'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $tabPeriods = [
            'today' => [
                'id' => 'today',
                'label' => __('Today'),
                'data' => $resultsToday,
                'bg' => 'rgba(255, 99, 132, 0.6)',
                'border' => 'rgba(255, 99, 132, 1)',
                'active' => true
            ],
            'yesterday' => [
                'id' => 'yesterday',
                'label' => __('Yesterday'),
                'data' => $resultsYesterday,
                'bg' => 'rgba(153, 102, 255, 0.6)',
                'border' => 'rgba(153, 102, 255, 1)',
                'active' => false
            ],
            '7days' => [
                'id' => '7days',
                'label' => __('Last 7 Days'),
                'data' => $results7Days,
                'bg' => 'rgba(54, 162, 235, 0.6)',
                'border' => 'rgba(54, 162, 235, 1)',
                'active' => false
            ],
            '30days' => [
                'id' => '30days',
                'label' => __('Last 30 Days'),
                'data' => $results30Days,
                'bg' => 'rgba(75, 192, 192, 0.6)',
                'border' => 'rgba(75, 192, 192, 1)',
                'active' => false
            ]
        ];
        ?>

        <div class="row mt-4">
            <div class="col-md-12">
                <ul class="nav nav-tabs nav-fill flex-nowrap overflow-auto" id="reportTabs" role="tablist">
                    <?php foreach ($tabPeriods as $key => $tab): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?php echo $tab['active'] ? ' active' : ''; ?>"
                                    id="tab-<?php echo $tab['id']; ?>"
                                    data-bs-toggle="tab"
                                    data-bs-target="#panel-<?php echo $tab['id']; ?>"
                                    type="button" role="tab"
                                    aria-controls="panel-<?php echo $tab['id']; ?>"
                                    aria-selected="<?php echo $tab['active'] ? 'true' : 'false'; ?>">
                                <?php echo $tab['label']; ?>
                                <span class="badge bg-secondary ms-1"><?php echo count($tab['data'] ?? []); ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content mt-3" id="reportTabContent">
                    <?php foreach ($tabPeriods as $key => $tab): ?>
                        <div class="tab-pane fade<?php echo $tab['active'] ? ' show active' : ''; ?>"
                             id="panel-<?php echo $tab['id']; ?>" role="tabpanel"
                             aria-labelledby="tab-<?php echo $tab['id']; ?>">
                            <h4 class="text-center mb-3"><?php echo $reportConfig['chartTitles'][$key]; ?></h4>
                            <canvas id="chart-<?php echo $tab['id']; ?>"></canvas>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var chartLabel = '<?php echo $reportConfig["chartLabel"]; ?>';
            var faqType = '<?php echo $reportConfig["faqLinkType"]; ?>';
            var chartInstances = {};

            var reportChartConfigs = {
                <?php foreach ($tabPeriods as $key => $tab): ?>
                '<?php echo $tab['id']; ?>': {
                    data: <?php echo json_encode($tab['data'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                    bg: '<?php echo $tab['bg']; ?>',
                    border: '<?php echo $tab['border']; ?>'
                },
                <?php endforeach; ?>
            };

            function initReportChart(tabId) {
                if (chartInstances[tabId]) return;
                var config = reportChartConfigs[tabId];
                if (!config) return;

                var canvas = document.getElementById('chart-' + tabId);
                if (!canvas) return;

                var labels = createFaqLinks(config.data, faqType);
                var counts = Object.values(config.data);
                chartInstances[tabId] = createBarChart(
                    canvas.getContext('2d'), labels, counts,
                    chartLabel, config.bg, config.border
                );
            }

            // Init the active tab chart immediately
            initReportChart('today');

            // Lazy init on tab show
            var tabButtons = document.querySelectorAll('#reportTabs button[data-bs-toggle="tab"]');
            tabButtons.forEach(function (btn) {
                btn.addEventListener('shown.bs.tab', function (e) {
                    var targetId = e.target.getAttribute('data-bs-target').replace('#panel-', '');
                    initReportChart(targetId);
                });
            });
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>
