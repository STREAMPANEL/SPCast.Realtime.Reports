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
<html lang="<?php echo htmlspecialchars($current_lang ?? 'en'); ?>">

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
        <div class="row mt-5">
            <div class="col-md-12">
                <canvas id="realtimeChart"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('realtimeChart').getContext('2d');
            const chartData = <?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            const myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($hours, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                    datasets: [{
                        label: '<?php echo $inoutConfig['chartLabels']['realtime']; ?>',
                        data: chartData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        fill: true,
                        tension: 0.1,
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: '<?php echo __('Hour of the day'); ?>'
                            },
                            ticks: {
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '<?php echo $inoutConfig['chartLabels']['realtimeY']; ?>'
                            }
                        }
                    }
                }
            });

            const ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
            const weeklyChartData = <?php echo json_encode($weeklyChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const weeklyDates = <?php echo json_encode($dates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            const weeklyChart = new Chart(ctxWeekly, {
                type: 'line',
                data: {
                    labels: weeklyDates,
                    datasets: [{
                        label: '<?php echo $inoutConfig['chartLabels']['weekly']; ?>',
                        data: weeklyChartData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: true,
                        tension: 0.1,
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: '<?php echo __('Date'); ?>'
                            },
                            ticks: {
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '<?php echo __('Users'); ?>'
                            }
                        }
                    }
                }
            });

            const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChartData = <?php echo json_encode($monthlyChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const monthlyDates = <?php echo json_encode($monthlyDates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            const monthlyChart = new Chart(ctxMonthly, {
                type: 'line',
                data: {
                    labels: monthlyDates,
                    datasets: [{
                        label: '<?php echo $inoutConfig['chartLabels']['monthly']; ?>',
                        data: monthlyChartData,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        fill: true,
                        tension: 0.1,
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: '<?php echo __('Date'); ?>'
                            },
                            ticks: {
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '<?php echo __('Users'); ?>'
                            }
                        }
                    }
                }
            });
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>
