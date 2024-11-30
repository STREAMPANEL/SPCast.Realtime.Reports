<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir          = 'cache/';
$cityFileToday     = $cacheDir . 'analytics_city_cache_today.json';
$cityFileYesterday = $cacheDir . 'analytics_city_cache_yesterday.json';
$cityFile7Days     = $cacheDir . 'analytics_city_cache_7days.json';
$cityFile30Days    = $cacheDir . 'analytics_city_cache_30days.json';
$cacheTime         = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithCity($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:city'],
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

function fetchAndCacheCityData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithCity($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $city  = $row->getDimensionValues()[1]->getValue();
                $count = $row->getMetricValues()[0]->getValue();
                if ($city !== "(not set)") {
                    $results[$city] = isset($results[$city]) ? $results[$city] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheCityData($cityFileToday, 'today');
$resultsYesterday = fetchAndCacheCityData($cityFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheCityData($cityFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheCityData($cityFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutzerstatistik nach Stadt - SPCast Live</title>
    <meta name="description" content="Detaillierte Daten zur Herkunft der Nutzer auf Stadtebene. Statistiken für heute, die letzten 7 und 30 Tage.">
    <meta name="keywords" content="spcast live, spcast städtestatistik, nutzer nach stadt, echtzeitdaten, netzwerkstatistiken, städteanalyse">
    <link rel="canonical" href="https://live.spcast.eu/city.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">Nutzerstatistik nach Stadt</h1>
            <p>Anonymisierte Statistiken zur Nutzung der Radiostationen im SPCast-Netzwerk, sortiert nach Städten. Erhalten Sie Einblicke in die lokale Verteilung der Aktivitäten.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Städtestatistik der Nutzer für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Stadtverteilung für heute</h4>
                <canvas id="cityChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Stadtverteilung für gestern</h4>
                <canvas id="cityChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Stadtverteilung der letzten 7 Tage</h4>
                <canvas id="cityChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Stadtverteilung der letzten 30 Tage</h4>
                <canvas id="cityChart30Days"></canvas>
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
            function createCityLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/usercity/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const cityDataToday = <?php echo json_encode($resultsToday); ?>;
            const cityLabelsToday = createCityLinks(cityDataToday);
            const cityCountsToday = Object.values(cityDataToday);

            const cityDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const cityLabelsYesterday = createCityLinks(cityDataYesterday);
            const cityCountsYesterday = Object.values(cityDataYesterday);

            const cityData7Days = <?php echo json_encode($results7Days); ?>;
            const cityLabels7Days = createCityLinks(cityData7Days);
            const cityCounts7Days = Object.values(cityData7Days);

            const cityData30Days = <?php echo json_encode($results30Days); ?>;
            const cityLabels30Days = createCityLinks(cityData30Days);
            const cityCounts30Days = Object.values(cityData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'City',
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
                                    text: 'Stadt'
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

            createChart(document.getElementById('cityChartToday').getContext('2d'), cityLabelsToday, cityCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('cityChartYesterday').getContext('2d'), cityLabelsYesterday, cityCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('cityChart7Days').getContext('2d'), cityLabels7Days, cityCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('cityChart30Days').getContext('2d'), cityLabels30Days, cityCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>