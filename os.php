<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir        = 'cache/';
$osFileToday     = $cacheDir . 'analytics_os_cache_today.json';
$osFileYesterday = $cacheDir . 'analytics_os_cache_yesterday.json';
$osFile7Days     = $cacheDir . 'analytics_os_cache_7days.json';
$osFile30Days    = $cacheDir . 'analytics_os_cache_30days.json';
$cacheTime       = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithOS($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:os'],
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

function fetchAndCacheOSData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithOS($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $os    = $row->getDimensionValues()[1]->getValue();
                $count = $row->getMetricValues()[0]->getValue();
                if ($os !== "(not set)") {
                    $results[$os] = isset($results[$os]) ? $results[$os] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheOSData($osFileToday, 'today');
$resultsYesterday = fetchAndCacheOSData($osFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheOSData($osFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheOSData($osFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betriebssystem-Statistiken - SPCast Live</title>
    <meta name="description" content="Überblick über die genutzten Betriebssysteme der Nutzer. Live-Daten und Statistiken für verschiedene Zeiträume.">
    <meta name="keywords" content="spcast live, spcast betriebssysteme, nutzerplattformen, os statistiken, echtzeitdaten, netzwerkplattformen">
    <link rel="canonical" href="https://live.spcast.eu/os.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
<?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <h1 class="text-center">Betriebssystem-Statistiken</h1>
        <p>Statistiken zu den von Nutzern verwendeten Betriebssystemen im SPCast-Netzwerk. Anonymisierte Daten bieten Einblicke in technische Vorlieben und Trends über verschiedene Zeiträume hinweg.
        </p>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Betriebssystemstatistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Betriebssystemverteilung für heute</h4>
                <canvas id="osChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Betriebssystemverteilung für gestern</h4>
                <canvas id="osChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Betriebssystemverteilung der letzten 7 Tage</h4>
                <canvas id="osChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Betriebssystemverteilung der letzten 30 Tage</h4>
                <canvas id="osChart30Days"></canvas>
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
            function createOSLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/useros/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const osDataToday = <?php echo json_encode($resultsToday); ?>;
            const osLabelsToday = createOSLinks(osDataToday);
            const osCountsToday = Object.values(osDataToday);

            const osDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const osLabelsYesterday = createOSLinks(osDataYesterday);
            const osCountsYesterday = Object.values(osDataYesterday);

            const osData7Days = <?php echo json_encode($results7Days); ?>;
            const osLabels7Days = createOSLinks(osData7Days);
            const osCounts7Days = Object.values(osData7Days);

            const osData30Days = <?php echo json_encode($results30Days); ?>;
            const osLabels30Days = createOSLinks(osData30Days);
            const osCounts30Days = Object.values(osData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Betriebssystem',
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
                                    text: 'Betriebssystem'
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

            createChart(document.getElementById('osChartToday').getContext('2d'), osLabelsToday, osCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('osChartYesterday').getContext('2d'), osLabelsYesterday, osCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('osChart7Days').getContext('2d'), osLabels7Days, osCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('osChart30Days').getContext('2d'), osLabels30Days, osCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>