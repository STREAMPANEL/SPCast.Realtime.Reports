<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir             = 'cache/';
$browserFileToday     = $cacheDir . 'analytics_browser_cache_today.json';
$browserFileYesterday = $cacheDir . 'analytics_browser_cache_yesterday.json';
$browserFile7Days     = $cacheDir . 'analytics_browser_cache_7days.json';
$browserFile30Days    = $cacheDir . 'analytics_browser_cache_30days.json';
$cacheTime            = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

require_once 'includes/initializeAnalytics.php';

function getListenerAddWithBrowser($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:browser'],
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

function fetchAndCacheBrowserData($cacheFile, $startDate, $endDate = 'today')
{

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
        return json_decode(file_get_contents($cacheFile), true);
    } else {
        $analytics = initializeAnalytics();
        $response  = getListenerAddWithBrowser($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $browser = $row->getDimensionValues()[1]->getValue();
                $count   = $row->getMetricValues()[0]->getValue();
                if ($browser !== "(not set)") {
                    $results[$browser] = isset($results[$browser]) ? $results[$browser] + $count : $count;
                }
            }
        }

        file_put_contents($cacheFile, json_encode($results));
        return $results;
    }
}

$resultsToday     = fetchAndCacheBrowserData($browserFileToday, 'today');
$resultsYesterday = fetchAndCacheBrowserData($browserFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheBrowserData($browserFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheBrowserData($browserFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browserstatistik für Heute, Gestern, 7 und letzten 30 Tage</title>
    <?php require_once "includes/head.php"; ?>
</head>

<body>

    <div class="container my-5">
        <h1 class="text-center">SPCast Statistiken</h1>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Browserstatistik für Heute, Gestern, 7 und letzten 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Browserverteilung für Heute</h4>
                <canvas id="browserChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Browserverteilung für Gestern</h4>
                <canvas id="browserChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Browserverteilung der letzten 7 Tage</h4>
                <canvas id="browserChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Browserverteilung der letzten 30 Tage</h4>
                <canvas id="browserChart30Days"></canvas>
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
            function createBrowserLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/useragent/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const browserDataToday = <?php echo json_encode($resultsToday); ?>;
            const browserLabelsToday = createBrowserLinks(browserDataToday);
            const browserCountsToday = Object.values(browserDataToday);

            const browserDataYesterday = <?php echo json_encode($resultsYesterday); ?>;
            const browserLabelsYesterday = createBrowserLinks(browserDataYesterday);
            const browserCountsYesterday = Object.values(browserDataYesterday);

            const browserData7Days = <?php echo json_encode($results7Days); ?>;
            const browserLabels7Days = createBrowserLinks(browserData7Days);
            const browserCounts7Days = Object.values(browserData7Days);

            const browserData30Days = <?php echo json_encode($results30Days); ?>;
            const browserLabels30Days = createBrowserLinks(browserData30Days);
            const browserCounts30Days = Object.values(browserData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Browser',
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
                                    text: 'Browser'
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

            createChart(document.getElementById('browserChartToday').getContext('2d'), browserLabelsToday, browserCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('browserChartYesterday').getContext('2d'), browserLabelsYesterday, browserCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('browserChart7Days').getContext('2d'), browserLabels7Days, browserCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('browserChart30Days').getContext('2d'), browserLabels30Days, browserCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>