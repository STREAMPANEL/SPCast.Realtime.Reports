<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir    = 'cache/';
$cacheFile   = $cacheDir . 'analytics_cache_in.json';
$historyFile = $cacheDir . 'analytics_history_in.json';
$weeklyFile  = $cacheDir . 'analytics_weekly_in.json';
$monthlyFile = $cacheDir . 'analytics_monthly_in.json';
$cacheTime   = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

require_once 'includes/initializeAnalytics.php';

function getRealTimeReport($analytics)
{

    $body = new Google_Service_AnalyticsData_RunRealtimeReportRequest([
        'metrics' => [['name' => 'activeUsers']],
    ]);

    return $analytics->properties->runRealtimeReport('properties/457854648', $body);
}

function getWeeklyReport($analytics)
{

    $body = new Google\Service\AnalyticsData\RunReportRequest([
        'dateRanges' => [
            ['startDate' => '7daysAgo', 'endDate' => 'yesterday'],
        ],
        'metrics'    => [
            ['name' => 'activeUsers'],
        ],
        'dimensions' => [
            ['name' => 'date'],
        ],
    ]);

    return $analytics->properties->runReport('properties/457854648', $body);
}

function getMonthlyReport($analytics)
{

    $body = new Google\Service\AnalyticsData\RunReportRequest([
        'dateRanges' => [
            ['startDate' => '30daysAgo', 'endDate' => 'yesterday'],
        ],
        'metrics'    => [
            ['name' => 'activeUsers'],
        ],
        'dimensions' => [
            ['name' => 'date'],
        ],
    ]);

    return $analytics->properties->runReport('properties/457854648', $body);
}

function fetchAndCacheData()
{

    global $cacheFile, $historyFile;
    $analytics = initializeAnalytics();
    $response  = getRealTimeReport($analytics);
    $rows      = $response->getRows();

    if (!empty($rows)) {
        $activeUsers = $rows[0]->getMetricValues()[0]->getValue();
        $data        = ['activeUsers' => $activeUsers, 'timestamp' => time()];

        file_put_contents($cacheFile, json_encode($data));

        $hour    = date('G');
        $history = [];

        if (file_exists($historyFile)) {
            $history = json_decode(file_get_contents($historyFile), true);
        }

        $history[$hour] = $activeUsers;

        file_put_contents($historyFile, json_encode($history));

        return $data;
    }

    return null;
}

function fetchAndCacheWeeklyData()
{

    global $weeklyFile;
    $analytics = initializeAnalytics();
    $response  = getWeeklyReport($analytics);
    $rows      = $response->getRows();

    $weeklyData = [];

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $date              = $row->getDimensionValues()[0]->getValue();
            $activeUsers       = $row->getMetricValues()[0]->getValue();
            $weeklyData[$date] = $activeUsers;
        }

        file_put_contents($weeklyFile, json_encode($weeklyData));
        return $weeklyData;
    }

    return null;
}

function fetchAndCacheMonthlyData()
{

    global $monthlyFile;
    $analytics = initializeAnalytics();
    $response  = getMonthlyReport($analytics);
    $rows      = $response->getRows();

    $monthlyData = [];

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $date               = $row->getDimensionValues()[0]->getValue();
            $activeUsers        = $row->getMetricValues()[0]->getValue();
            $monthlyData[$date] = $activeUsers;
        }

        file_put_contents($monthlyFile, json_encode($monthlyData));
        return $monthlyData;
    }

    return null;
}

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    $data = json_decode(file_get_contents($cacheFile), true);
} else {
    $data = fetchAndCacheData();
}

$history = [];
if (file_exists($historyFile)) {
    $history = json_decode(file_get_contents($historyFile), true);
}

if (file_exists($weeklyFile) && (time() - filemtime($weeklyFile)) < (24 * 60 * 60)) {
    $weeklyData = json_decode(file_get_contents($weeklyFile), true);
} else {
    $weeklyData = fetchAndCacheWeeklyData();
}

if (file_exists($monthlyFile) && (time() - filemtime($monthlyFile)) < (24 * 60 * 60)) {
    $monthlyData = json_decode(file_get_contents($monthlyFile), true);
} else {
    $monthlyData = fetchAndCacheMonthlyData();
}

$dates           = [];
$weeklyChartData = [];
for ($i = 7; $i > 0; $i--) {
    $date              = date('Ymd', strtotime("-$i days"));
    $formattedDate     = date('d.m', strtotime("-$i days"));
    $dates[]           = $formattedDate;
    $weeklyChartData[] = isset($weeklyData[$date]) ? $weeklyData[$date] : 0;
}

$monthlyDates     = [];
$monthlyChartData = [];
for ($i = 30; $i > 0; $i--) {
    $date               = date('Ymd', strtotime("-$i days"));
    $formattedDate      = date('d.m', strtotime("-$i days"));
    $monthlyDates[]     = $formattedDate;
    $monthlyChartData[] = isset($monthlyData[$date]) ? $monthlyData[$date] : 0;
}

$hours     = range(0, 23);
$chartData = [];
foreach ($hours as $hour) {
    $chartData[] = isset($history[$hour]) ? $history[$hour] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eingehende Nutzerstatistik - SPCast Live</title>
    <meta name="description" content="Verfolgen Sie alle Nutzer, die Sender innerhalb der letzten 30 Minuten eingeschaltet haben. Live-Statistiken und Echtzeit-Einblicke.">
    <meta name="keywords" content="spcast live, spcast nutzerstatistik, eingehende nutzer, sender einschalten, echtzeitdaten, netzwerkaktivitäten">
    <link rel="canonical" href="https://live.spcast.eu/in.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">Eingehende Nutzerstatistik</h1>
            <p>Diese Statistik zeigt anonymisierte Daten zu Nutzern, die innerhalb der letzten 30 Minuten einen Sender im SPCast-Netzwerk eingeschaltet haben. Analysieren Sie Trends und allgemeine
                Nutzungsaktivitäten.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">
                            Eingeschaltet in den letzten 30 Minuten
                        </h3>
                        <small>Nutzer, die in den letzten 30 Minuten einen Sender eingeschaltet haben.</small>
                        <p id="activeUsers" class="display-4"><?php echo $data['activeUsers']; ?></p>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
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
        let countdown = 45;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            countdown--;
            if (countdown <= 0) {
                location.reload();
            } else {
                countdownElement.innerText = countdown;
            }
        }

        setInterval(updateCountdown, 1000);

        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('realtimeChart').getContext('2d');
            const chartData = <?php echo json_encode($chartData); ?>;

            const myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($hours); ?>,
                    datasets: [{
                        label: 'Eingeschaltet pro 30 Minuten',
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
                                text: 'Stunde des Tages'
                            },
                            ticks: {
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Eingeschaltet pro 30 Minuten'
                            }
                        }
                    }
                }
            });

            const ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
            const weeklyChartData = <?php echo json_encode($weeklyChartData); ?>;
            const weeklyDates = <?php echo json_encode($dates); ?>;

            const weeklyChart = new Chart(ctxWeekly, {
                type: 'line',
                data: {
                    labels: weeklyDates,
                    datasets: [{
                        label: 'Eingeschaltet in den letzten 7 Tagen',
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
                                text: 'Datum'
                            },
                            ticks: {
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nutzer'
                            }
                        }
                    }
                }
            });

            const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChartData = <?php echo json_encode($monthlyChartData); ?>;
            const monthlyDates = <?php echo json_encode($monthlyDates); ?>;

            const monthlyChart = new Chart(ctxMonthly, {
                type: 'line',
                data: {
                    labels: monthlyDates,
                    datasets: [{
                        label: 'Eingeschaltet in den letzten 30 Tagen',
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
                                text: 'Datum'
                            },
                            ticks: {
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nutzer'
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