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

$cacheDir              = 'cache/';
$protocolFileToday     = $cacheDir . 'analytics_protocol_cache_today.json';
$protocolFileYesterday = $cacheDir . 'analytics_protocol_cache_yesterday.json';
$protocolFile7Days     = $cacheDir . 'analytics_protocol_cache_7days.json';
$protocolFile30Days    = $cacheDir . 'analytics_protocol_cache_30days.json';
$cacheTime             = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithProtocol($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:protocol'],
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

function fetchAndCacheProtocolData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithProtocol($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $protocol = $row->getDimensionValues()[1]->getValue();
                $count    = $row->getMetricValues()[0]->getValue();
                if ($protocol !== "(not set)") {
                    $results[$protocol] = isset($results[$protocol]) ? $results[$protocol] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheProtocolData($protocolFileToday, 'today');
$resultsYesterday = fetchAndCacheProtocolData($protocolFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheProtocolData($protocolFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheProtocolData($protocolFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protokoll-Statistiken - SPCast Live</title>
    <meta name="description" content="Zeigt die Protokolle der Nutzeraktivitäten im Netzwerk. Statistiken für tägliche, wöchentliche und monatliche Auswertungen.">
    <meta name="keywords" content="spcast live, spcast protokollstatistiken, nutzerprotokoll, netzwerkprotokolle, echtzeitstatistiken, datenprotokolle">
    <link rel="canonical" href="https://live.spcast.eu/protocol.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">Protokoll-Statistiken</h1>
            <p>Sehen Sie anonymisierte Protokollstatistiken der Nutzeraktivitäten im SPCast-Netzwerk. Diese Daten geben Aufschluss über die Nutzung technischer Protokolle für verschiedene Zeiträume.
            </p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Protokollstatistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Protokollverteilung für heute</h4>
                <canvas id="protocolChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Protokollverteilung für gestern</h4>
                <canvas id="protocolChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Protokollverteilung der letzten 7 Tage</h4>
                <canvas id="protocolChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Protokollverteilung der letzten 30 Tage</h4>
                <canvas id="protocolChart30Days"></canvas>
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
            function createProtocolLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/userprotocol/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const protocolDataToday = <?php echo json_encode($resultsToday); ?>;
            const protocolLabelsToday = createProtocolLinks(protocolDataToday);
            const protocolCountsToday = Object.values(protocolDataToday);

            const protocolDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const protocolLabelsYesterday = createProtocolLinks(protocolDataYesterday);
            const protocolCountsYesterday = Object.values(protocolDataYesterday);

            const protocolData7Days = <?php echo json_encode($results7Days); ?>;
            const protocolLabels7Days = createProtocolLinks(protocolData7Days);
            const protocolCounts7Days = Object.values(protocolData7Days);

            const protocolData30Days = <?php echo json_encode($results30Days); ?>;
            const protocolLabels30Days = createProtocolLinks(protocolData30Days);
            const protocolCounts30Days = Object.values(protocolData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Protokoll',
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
                                    text: 'Protokoll'
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

            createChart(document.getElementById('protocolChartToday').getContext('2d'), protocolLabelsToday, protocolCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('protocolChartYesterday').getContext('2d'), protocolLabelsYesterday, protocolCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('protocolChart7Days').getContext('2d'), protocolLabels7Days, protocolCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('protocolChart30Days').getContext('2d'), protocolLabels30Days, protocolCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>