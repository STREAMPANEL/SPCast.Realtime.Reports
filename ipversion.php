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

$cacheDir               = 'cache/';
$ipVersionFileToday     = $cacheDir . 'analytics_ipversion_cache_today.json';
$ipVersionFileYesterday = $cacheDir . 'analytics_ipversion_cache_yesterday.json';
$ipVersionFile7Days     = $cacheDir . 'analytics_ipversion_cache_7days.json';
$ipVersionFile30Days    = $cacheDir . 'analytics_ipversion_cache_30days.json';
$cacheTime              = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithIPVersion($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:ipversion'],
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

function fetchAndCacheIPVersionData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithIPVersion($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $ipversion = $row->getDimensionValues()[1]->getValue();
                $count     = $row->getMetricValues()[0]->getValue();
                if ($ipversion !== "(not set)") {
                    $results[$ipversion] = isset($results[$ipversion]) ? $results[$ipversion] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheIPVersionData($ipVersionFileToday, 'today');
$resultsYesterday = fetchAndCacheIPVersionData($ipVersionFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheIPVersionData($ipVersionFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheIPVersionData($ipVersionFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP-Version-Statistiken - SPCast Live</title>
    <meta name="description" content="Zeigt die Nutzung von IPv4 und IPv6 durch Nutzer im Netzwerk. Echtzeitdaten und Statistiken für verschiedene Zeiträume.">
    <meta name="keywords" content="spcast live, spcast ip-version, ipv4, ipv6, ip statistiken, nutzerdaten, netzwerkprotokolle">
    <link rel="canonical" href="https://live.spcast.eu/ipversion.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">IP-Version-Statistiken</h1>
            <p>Anonymisierte Daten zur Nutzung von IPv4 und IPv6 im SPCast-Netzwerk. Diese Statistiken geben Aufschluss über die Verteilung der IP-Versionen innerhalb der Radiostationen.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">IP-Version-Statistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">IP-Version-Verteilung für heute</h4>
                <canvas id="ipVersionChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">IP-Version-Verteilung für gestern</h4>
                <canvas id="ipVersionChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">IP-Version-Verteilung der letzten 7 Tage</h4>
                <canvas id="ipVersionChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">IP-Version-Verteilung der letzten 30 Tage</h4>
                <canvas id="ipVersionChart30Days"></canvas>
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
            function createIPVersionLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/useripversion/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const ipVersionDataToday = <?php echo json_encode($resultsToday); ?>;
            const ipVersionLabelsToday = createIPVersionLinks(ipVersionDataToday);
            const ipVersionCountsToday = Object.values(ipVersionDataToday);

            const ipVersionDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const ipVersionLabelsYesterday = createIPVersionLinks(ipVersionDataYesterday);
            const ipVersionCountsYesterday = Object.values(ipVersionDataYesterday);

            const ipVersionData7Days = <?php echo json_encode($results7Days); ?>;
            const ipVersionLabels7Days = createIPVersionLinks(ipVersionData7Days);
            const ipVersionCounts7Days = Object.values(ipVersionData7Days);

            const ipVersionData30Days = <?php echo json_encode($results30Days); ?>;
            const ipVersionLabels30Days = createIPVersionLinks(ipVersionData30Days);
            const ipVersionCounts30Days = Object.values(ipVersionData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'IP-Version',
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
                                    text: 'IP-Version'
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

            createChart(document.getElementById('ipVersionChartToday').getContext('2d'), ipVersionLabelsToday, ipVersionCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('ipVersionChartYesterday').getContext('2d'), ipVersionLabelsYesterday, ipVersionCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('ipVersionChart7Days').getContext('2d'), ipVersionLabels7Days, ipVersionCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('ipVersionChart30Days').getContext('2d'), ipVersionLabels30Days, ipVersionCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>