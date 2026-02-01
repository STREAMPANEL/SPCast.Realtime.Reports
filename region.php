<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir            = 'cache/';
$regionFileToday     = $cacheDir . 'analytics_region_cache_today.json';
$regionFileYesterday = $cacheDir . 'analytics_region_cache_yesterday.json';
$regionFile7Days     = $cacheDir . 'analytics_region_cache_7days.json';
$regionFile30Days    = $cacheDir . 'analytics_region_cache_30days.json';
$cacheTime           = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithRegion($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:region'],
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

function fetchAndCacheRegionData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithRegion($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $region = $row->getDimensionValues()[1]->getValue();
                $count  = $row->getMetricValues()[0]->getValue();
                if ($region !== "(not set)") {
                    $results[$region] = isset($results[$region]) ? $results[$region] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheRegionData($regionFileToday, 'today');
$resultsYesterday = fetchAndCacheRegionData($regionFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheRegionData($regionFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheRegionData($regionFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutzerstatistik nach Bundesland - SPCast Live</title>
    <meta name="description" content="Zeigt Nutzeraktivitäten nach Bundesländern. Ausführliche Statistiken für verschiedene Zeiträume, tages- und wochenweise.">
    <meta name="keywords" content="spcast live, spcast bundesländerstatistik, nutzer nach bundesland, netzwerkdaten, echtzeitstatistiken">
    <link rel="canonical" href="https://live.spcast.eu/region.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">Nutzerstatistik nach Bundesland</h1>
            <p>Sehen Sie anonymisierte Daten zu Nutzeraktivitäten im SPCast-Netzwerk nach Bundesländern. Diese Statistiken zeigen, wie die Nutzung geografisch verteilt ist, basierend auf tages- und
                wochenweisen Auswertungen.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Bundeslandstatistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Verteilung nach Bundesland für heute</h4>
                <canvas id="regionChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Verteilung nach Bundesland für gestern</h4>
                <canvas id="regionChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Verteilung nach Bundesland der letzten 7 Tage</h4>
                <canvas id="regionChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Verteilung nach Bundesland der letzten 30 Tage</h4>
                <canvas id="regionChart30Days"></canvas>
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
            function createRegionLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/userregion/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const regionDataToday = <?php echo json_encode($resultsToday); ?>;
            const regionLabelsToday = createRegionLinks(regionDataToday);
            const regionCountsToday = Object.values(regionDataToday);

            const regionDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const regionLabelsYesterday = createRegionLinks(regionDataYesterday);
            const regionCountsYesterday = Object.values(regionDataYesterday);

            const regionData7Days = <?php echo json_encode($results7Days); ?>;
            const regionLabels7Days = createRegionLinks(regionData7Days);
            const regionCounts7Days = Object.values(regionData7Days);

            const regionData30Days = <?php echo json_encode($results30Days); ?>;
            const regionLabels30Days = createRegionLinks(regionData30Days);
            const regionCounts30Days = Object.values(regionData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Bundesland',
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
                                    text: 'Bundesland'
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

            createChart(document.getElementById('regionChartToday').getContext('2d'), regionLabelsToday, regionCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('regionChartYesterday').getContext('2d'), regionLabelsYesterday, regionCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('regionChart7Days').getContext('2d'), regionLabels7Days, regionCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('regionChart30Days').getContext('2d'), regionLabels30Days, regionCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>