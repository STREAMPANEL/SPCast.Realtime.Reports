<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir           = 'cache/';
$mountFileToday     = $cacheDir . 'analytics_mount_cache_today.json';
$mountFileYesterday = $cacheDir . 'analytics_mount_cache_yesterday.json';
$mountFile7Days     = $cacheDir . 'analytics_mount_cache_7days.json';
$mountFile30Days    = $cacheDir . 'analytics_mount_cache_30days.json';
$cacheTime          = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithMount($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:mount'],
        ],
        'metrics'         => [
            ['name' => 'eventCount'],
        ],
        'dateRanges'      => [
            ['startDate' => $startDate, 'endDate' => $endDate],
        ],
        'dimensionFilter' => [
            'filter' => [
                'fieldName'    => 'eventName',
                'stringFilter' => [
                    'value' => 'listener_add',
                ],
            ],
        ],
    ]);

    return $analytics->properties->runReport('properties/453756722', $body);
}

function fetchAndCacheMountData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithMount($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $mount = $row->getDimensionValues()[1]->getValue();
                $count = $row->getMetricValues()[0]->getValue();
                if ($mount !== "(not set)") {
                    $results[$mount] = isset($results[$mount]) ? $results[$mount] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheMountData($mountFileToday, 'today');
$resultsYesterday = fetchAndCacheMountData($mountFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheMountData($mountFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheMountData($mountFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mountpoint-Statistiken - SPCast Live</title>
    <meta name="description" content="Detaillierte Statistiken über die genutzten Mountpoints im Netzwerk. Analysen für verschiedene Zeiträume verfügbar.">
    <meta name="keywords" content="spcast live, spcast mountpoints, nutzerdaten, echtzeitstatistiken, netzwerkanalysen, mountpoint statistiken">
    <link rel="canonical" href="https://live.spcast.eu/mount.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
<?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <h1 class="text-center">Mountpoint-Statistiken</h1>
        <p>Erhalten Sie detaillierte, anonymisierte Daten zu den genutzten Mountpoints im SPCast-Netzwerk. Diese Statistiken zeigen allgemeine Trends zur Nutzung der Radiostationen.</p>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Mountpoint-Statistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Mountpointverteilung für heute</h4>
                <canvas id="mountChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Mountpointverteilung für gestern</h4>
                <canvas id="mountChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Mountpointverteilung der letzten 7 Tage</h4>
                <canvas id="mountChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Mountpointverteilung der letzten 30 Tage</h4>
                <canvas id="mountChart30Days"></canvas>
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
            function createMountLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/usermount/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const mountDataToday = <?php echo json_encode($resultsToday); ?>;
            const mountLabelsToday = createMountLinks(mountDataToday);
            const mountCountsToday = Object.values(mountDataToday);

            const mountDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const mountLabelsYesterday = createMountLinks(mountDataYesterday);
            const mountCountsYesterday = Object.values(mountDataYesterday);

            const mountData7Days = <?php echo json_encode($results7Days); ?>;
            const mountLabels7Days = createMountLinks(mountData7Days);
            const mountCounts7Days = Object.values(mountData7Days);

            const mountData30Days = <?php echo json_encode($results30Days); ?>;
            const mountLabels30Days = createMountLinks(mountData30Days);
            const mountCounts30Days = Object.values(mountData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Mountpoint',
                            data: data,
                            backgroundColor: backgroundColor,
                            borderColor: borderColor,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        onClick: (event, elements) => {
                            if (elements.length > 0) {
                                const index = elements[0].index;
                                const url = labels[index].url;
                                window.open(url, '_blank');
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                onClick: (e, legendItem, legend) => {
                                    const index = legendItem.datasetIndex;
                                    const chart = legend.chart;
                                    const meta = chart.getDatasetMeta(index);

                                    meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
                                    chart.update();
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.raw + ' Nutzer';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Mountpoint'
                                },
                                ticks: {
                                    autoSkip: true
                                }
                            },
                            y: {
                                type: 'logarithmic',
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Anzahl der Nutzer'
                                },
                                ticks: {
                                    callback: function (value) {
                                        if (Number.isInteger(Math.log10(value))) {
                                            return value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createChart(document.getElementById('mountChartToday').getContext('2d'), mountLabelsToday, mountCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('mountChartYesterday').getContext('2d'), mountLabelsYesterday, mountCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('mountChart7Days').getContext('2d'), mountLabels7Days, mountCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('mountChart30Days').getContext('2d'), mountLabels30Days, mountCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>