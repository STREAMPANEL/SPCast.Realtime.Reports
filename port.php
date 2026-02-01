<?php
// Security headers
$allowed_origins = ['https://live.spcast.eu', 'https://spcast.eu'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: text/html; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Resource limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);

require_once 'vendor/autoload.php';

$cacheDir          = 'cache/';
$portFileToday     = $cacheDir . 'analytics_port_cache_today.json';
$portFileYesterday = $cacheDir . 'analytics_port_cache_yesterday.json';
$portFile7Days     = $cacheDir . 'analytics_port_cache_7days.json';
$portFile30Days    = $cacheDir . 'analytics_port_cache_30days.json';
$cacheTime         = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithPort($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:port'],
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

function fetchAndCachePortData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithPort($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $port  = $row->getDimensionValues()[1]->getValue();
                $count = $row->getMetricValues()[0]->getValue();
                if ($port !== "(not set)") {
                    $results[$port] = isset($results[$port]) ? $results[$port] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCachePortData($portFileToday, 'today');
$resultsYesterday = fetchAndCachePortData($portFileYesterday, 'yesterday');
$results7Days     = fetchAndCachePortData($portFile7Days, '7daysAgo');
$results30Days    = fetchAndCachePortData($portFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port-Statistiken - SPCast Live</title>
    <meta name="description" content="Statistiken zu den von Nutzern genutzten Ports im Netzwerk. Echtzeitdaten für tägliche und wöchentliche Analysen.">
    <meta name="keywords" content="spcast live, spcast ports, nutzerstatistik ports, netzwerkdaten, echtzeitanalysen, portstatistiken">
    <link rel="canonical" href="https://live.spcast.eu/port.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">Port-Statistiken</h1>
            <p>Anonymisierte Statistiken zu den von Nutzern genutzten Ports im SPCast-Netzwerk. Diese Daten helfen, allgemeine Trends und technische Präferenzen der Radiostationen zu erkennen.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Portstatistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Portverteilung für heute</h4>
                <canvas id="portChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Portverteilung für gestern</h4>
                <canvas id="portChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Portverteilung der letzten 7 Tage</h4>
                <canvas id="portChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Portverteilung der letzten 30 Tage</h4>
                <canvas id="portChart30Days"></canvas>
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
            function createPortLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/userport/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const portDataToday = <?php echo json_encode($resultsToday); ?>;
            const portLabelsToday = createPortLinks(portDataToday);
            const portCountsToday = Object.values(portDataToday);

            const portDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const portLabelsYesterday = createPortLinks(portDataYesterday);
            const portCountsYesterday = Object.values(portDataYesterday);

            const portData7Days = <?php echo json_encode($results7Days); ?>;
            const portLabels7Days = createPortLinks(portData7Days);
            const portCounts7Days = Object.values(portData7Days);

            const portData30Days = <?php echo json_encode($results30Days); ?>;
            const portLabels30Days = createPortLinks(portData30Days);
            const portCounts30Days = Object.values(portData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Port',
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
                                    text: 'Port'
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

            createChart(document.getElementById('portChartToday').getContext('2d'), portLabelsToday, portCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('portChartYesterday').getContext('2d'), portLabelsYesterday, portCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('portChart7Days').getContext('2d'), portLabels7Days, portCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('portChart30Days').getContext('2d'), portLabels30Days, portCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>