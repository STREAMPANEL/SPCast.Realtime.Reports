<?php

declare(strict_types=1);

// includes/inout_template.php
require_once 'includes/i18n.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir = 'cache/';
$cacheFile = $cacheDir . 'analytics_cache_' . $inoutConfig['type'] . '.json';
$historyFile = $cacheDir . 'analytics_history_' . $inoutConfig['type'] . '.json';
$weeklyFile = $cacheDir . 'analytics_weekly_' . $inoutConfig['type'] . '.json';
$monthlyFile = $cacheDir . 'analytics_monthly_' . $inoutConfig['type'] . '.json';
$cacheTime = 45;

require_once 'includes/initializeAnalytics.php';
ensureSecureCacheDirectory($cacheDir);
require_once 'includes/config.php';

$gaPropertyId = $inoutConfig['gaPropertyId'];

$logger = \App\Logger::createDefault();
$analyticsClient = initializeAnalytics();
$analyticsService = new \App\AnalyticsService($analyticsClient, $logger);

$data = $analyticsService->getCachedData($cacheFile, function () use ($historyFile, $gaPropertyId, $analyticsService) {
    $response = $analyticsService->getRealTimeReport($gaPropertyId);

    if ($response === null) {
        return null;
    }

    $rows = $response->getRows();

    if (!empty($rows)) {
        $activeUsers = $rows[0]->getMetricValues()[0]->getValue();
        $data = ['activeUsers' => $activeUsers, 'timestamp' => time()];

        // Update history
        $hour = date('G');
        $history = [];

        if (file_exists($historyFile)) {
            $history = json_decode(file_get_contents($historyFile), true);
        }

        $history[$hour] = $activeUsers;

        file_put_contents($historyFile, json_encode($history));

        return $data;
    }

    return null;
}, 45);

$history = [];
if (file_exists($historyFile)) {
    $history = json_decode(file_get_contents($historyFile), true);
}

$weeklyData = $analyticsService->getCachedData($weeklyFile, function () use ($gaPropertyId, $analyticsService) {
    $response = $analyticsService->getWeeklyReport($gaPropertyId);

    if ($response === null) {
        return null;
    }

    $rows = $response->getRows();

    $weeklyData = [];

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $date = $row->getDimensionValues()[0]->getValue();
            $activeUsers = $row->getMetricValues()[0]->getValue();
            $weeklyData[$date] = $activeUsers;
        }

        return $weeklyData;
    }

    return null;
}, 24 * 60 * 60);

$monthlyData = $analyticsService->getCachedData($monthlyFile, function () use ($gaPropertyId, $analyticsService) {
    $response = $analyticsService->getMonthlyReport($gaPropertyId);

    if ($response === null) {
        return null;
    }

    $rows = $response->getRows();

    $monthlyData = [];

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $date = $row->getDimensionValues()[0]->getValue();
            $activeUsers = $row->getMetricValues()[0]->getValue();
            $monthlyData[$date] = $activeUsers;
        }

        return $monthlyData;
    }

    return null;
}, 24 * 60 * 60);

$dates = [];
$weeklyChartData = [];
for ($i = 7; $i > 0; $i--) {
    $date = date('Ymd', strtotime("-$i days"));
    $formattedDate = date('d.m', strtotime("-$i days"));
    $dates[] = $formattedDate;
    $weeklyChartData[] = isset($weeklyData[$date]) ? $weeklyData[$date] : 0;
}

$monthlyDates = [];
$monthlyChartData = [];
for ($i = 30; $i > 0; $i--) {
    $date = date('Ymd', strtotime("-$i days"));
    $formattedDate = date('d.m', strtotime("-$i days"));
    $monthlyDates[] = $formattedDate;
    $monthlyChartData[] = isset($monthlyData[$date]) ? $monthlyData[$date] : 0;
}

$hours = range(0, 23);
$chartData = array_values(array_replace(array_fill_keys($hours, 0), array_intersect_key($history, array_flip($hours))));
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang ?? 'de'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $inoutConfig['pageTitle']; ?></title>
    <meta name="description" content="<?php echo $inoutConfig['pageDescription']; ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($inoutConfig['keywords'], ENT_QUOTES); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($inoutConfig['canonicalUrl'], ENT_QUOTES); ?>" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center"><?php echo $inoutConfig['headerTitle']; ?></h1>
            <p><?php echo $inoutConfig['headerDesc']; ?></p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">
                            <?php echo $inoutConfig['cardTitle']; ?>
                        </h3>
                        <small><?php echo $inoutConfig['cardDesc']; ?></small>
                        <p id="activeUsers" class="display-4"><?php echo isset($data['activeUsers']) ? htmlspecialchars($data['activeUsers']) : 0; ?></p>
                        <p><?php echo __('Next update in'); ?> <span id="countdown">45</span> <?php echo __('seconds'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <ul class="nav nav-tabs nav-fill flex-nowrap overflow-auto" id="inoutTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-realtime" data-bs-toggle="tab"
                                data-bs-target="#panel-realtime" type="button" role="tab"
                                aria-controls="panel-realtime" aria-selected="true">
                            <?php echo __('Today'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-weekly" data-bs-toggle="tab"
                                data-bs-target="#panel-weekly" type="button" role="tab"
                                aria-controls="panel-weekly" aria-selected="false">
                            <?php echo __('Last 7 Days'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-monthly" data-bs-toggle="tab"
                                data-bs-target="#panel-monthly" type="button" role="tab"
                                aria-controls="panel-monthly" aria-selected="false">
                            <?php echo __('Last 30 Days'); ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="inoutTabContent">
                    <div class="tab-pane fade show active" id="panel-realtime" role="tabpanel" aria-labelledby="tab-realtime">
                        <canvas id="realtimeChart"></canvas>
                    </div>
                    <div class="tab-pane fade" id="panel-weekly" role="tabpanel" aria-labelledby="tab-weekly">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                    <div class="tab-pane fade" id="panel-monthly" role="tabpanel" aria-labelledby="tab-monthly">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var chartInstances = {};

            var realtimeData = <?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var realtimeHours = <?php echo json_encode($hours, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var weeklyChartData = <?php echo json_encode($weeklyChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var weeklyDates = <?php echo json_encode($dates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var monthlyChartDataArr = <?php echo json_encode($monthlyChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var monthlyDatesArr = <?php echo json_encode($monthlyDates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            function createLineChart(canvasId, labels, data, label, xLabel, yLabel, bgColor, borderColor) {
                var canvas = document.getElementById(canvasId);
                if (!canvas) return null;
                return new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: data,
                            backgroundColor: bgColor,
                            borderColor: borderColor,
                            fill: true,
                            tension: 0.1,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        scales: {
                            x: {
                                title: { display: true, text: xLabel },
                                ticks: { autoSkip: true }
                            },
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: yLabel }
                            }
                        }
                    }
                });
            }

            var inoutCharts = {
                'realtime': function () {
                    return createLineChart('realtimeChart', realtimeHours, realtimeData,
                        '<?php echo $inoutConfig['chartLabels']['realtime']; ?>',
                        '<?php echo __('Hour of the day'); ?>',
                        '<?php echo $inoutConfig['chartLabels']['realtimeY']; ?>',
                        'rgba(54, 162, 235, 0.2)', 'rgba(54, 162, 235, 1)');
                },
                'weekly': function () {
                    return createLineChart('weeklyChart', weeklyDates, weeklyChartData,
                        '<?php echo $inoutConfig['chartLabels']['weekly']; ?>',
                        '<?php echo __('Date'); ?>', '<?php echo __('Users'); ?>',
                        'rgba(75, 192, 192, 0.2)', 'rgba(75, 192, 192, 1)');
                },
                'monthly': function () {
                    return createLineChart('monthlyChart', monthlyDatesArr, monthlyChartDataArr,
                        '<?php echo $inoutConfig['chartLabels']['monthly']; ?>',
                        '<?php echo __('Date'); ?>', '<?php echo __('Users'); ?>',
                        'rgba(153, 102, 255, 0.2)', 'rgba(153, 102, 255, 1)');
                }
            };

            // Init active tab chart
            chartInstances['realtime'] = inoutCharts['realtime']();

            // Lazy init on tab show
            var tabButtons = document.querySelectorAll('#inoutTabs button[data-bs-toggle="tab"]');
            tabButtons.forEach(function (btn) {
                btn.addEventListener('shown.bs.tab', function (e) {
                    var targetId = e.target.getAttribute('data-bs-target').replace('#panel-', '');
                    if (!chartInstances[targetId] && inoutCharts[targetId]) {
                        chartInstances[targetId] = inoutCharts[targetId]();
                    }
                });
            });
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>

