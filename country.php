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

$cacheDir             = 'cache/';
$countryFileToday     = $cacheDir . 'analytics_country_cache_today.json';
$countryFileYesterday = $cacheDir . 'analytics_country_cache_yesterday.json';
$countryFile7Days     = $cacheDir . 'analytics_country_cache_7days.json';
$countryFile30Days    = $cacheDir . 'analytics_country_cache_30days.json';
$cacheTime            = 45;

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

require_once 'includes/initializeAnalytics.php';
require_once 'includes/cacheCleanup.php';

function getListenerAddWithCountry($analytics, $startDate, $endDate = 'today')
{

    $body = new Google_Service_AnalyticsData_RunReportRequest([
        'dimensions'      => [
            ['name' => 'eventName'],
            ['name' => 'customEvent:country'],
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

function fetchAndCacheCountryData($cacheFile, $startDate, $endDate = 'today')
{
    try {
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 45) {
            $cachedData = file_get_contents($cacheFile);
            if ($cachedData === false) {
                throw new Exception('Failed to read cache file');
            }
            $decoded = json_decode($cachedData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in cache: ' . json_last_error_msg());
            }
            return $decoded;
        }

        $analytics = initializeAnalytics();
        $response = getListenerAddWithCountry($analytics, $startDate, $endDate);

        $results = [];
        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $country = htmlspecialchars($row->getDimensionValues()[1]->getValue(), ENT_QUOTES, 'UTF-8');
                $count = (int)$row->getMetricValues()[0]->getValue();
                if ($country !== "(not set)") {
                    $results[$country] = isset($results[$country]) ? $results[$country] + $count : $count;
                }
            }
        }

        $jsonData = json_encode($results);
        if ($jsonData === false) {
            throw new Exception('Failed to encode data to JSON');
        }
        
        if (file_put_contents($cacheFile, $jsonData, LOCK_EX) === false) {
            throw new Exception('Failed to write cache file');
        }
        
        return $results;
    } catch (Exception $e) {
        error_log('Analytics Error: ' . $e->getMessage());
        return [];
    }
}

$resultsToday     = fetchAndCacheCountryData($countryFileToday, 'today');
$resultsYesterday = fetchAndCacheCountryData($countryFileYesterday, 'yesterday');
$results7Days     = fetchAndCacheCountryData($countryFile7Days, '7daysAgo');
$results30Days    = fetchAndCacheCountryData($countryFile30Days, '30daysAgo');
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutzerstatistik nach Land - SPCast Live</title>
    <meta name="description" content="Analyse der Nutzeraktivitäten nach Ländern. Detaillierte Daten für heute, gestern, die letzten 7 und 30 Tage.">
    <meta name="keywords" content="spcast live, spcast länderstatistik, nutzer nach land, echtzeitdaten, netzwerkstatistiken, länderverteilung">
    <link rel="canonical" href="https://live.spcast.eu/country.php" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center">Nutzerstatistik nach Land</h1>
            <p>Analysieren Sie, aus welchen Ländern Nutzer im SPCast-Netzwerk auf Radiostationen zugreifen. Die Statistiken sind anonymisiert und bieten tägliche, wöchentliche und monatliche
                Auswertungen.
            </p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">Länderstatistik für heute, gestern, die letzten 7 und 30 Tage</h3>
                        <p>Nächste Aktualisierung in <span id="countdown">45</span> Sekunden</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Länderverteilung für heute</h4>
                <canvas id="countryChartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Länderverteilung für gestern</h4>
                <canvas id="countryChartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Länderverteilung der letzten 7 Tage</h4>
                <canvas id="countryChart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center">Länderverteilung der letzten 30 Tage</h4>
                <canvas id="countryChart30Days"></canvas>
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
            function createCountryLinks(data) {
                return Object.keys(data).map(label => {
                    return {
                        label: label,
                        url: `https://www.spcast.eu/faq/statistik/usercountry/${encodeURIComponent(label.toLowerCase())}/`
                    };
                });
            }

            const countryDataToday = <?php echo htmlspecialchars(json_encode($resultsToday), ENT_QUOTES, 'UTF-8'); ?>;
            const countryLabelsToday = createCountryLinks(countryDataToday);
            const countryCountsToday = Object.values(countryDataToday);

            const countryDataYesterday = <?php echo htmlspecialchars(json_encode($resultsYesterday), ENT_QUOTES, 'UTF-8'); ?>;
            const countryLabelsYesterday = createCountryLinks(countryDataYesterday);
            const countryCountsYesterday = Object.values(countryDataYesterday);

            const countryData7Days = <?php echo htmlspecialchars(json_encode($results7Days), ENT_QUOTES, 'UTF-8'); ?>;
            const countryLabels7Days = createCountryLinks(countryData7Days);
            const countryCounts7Days = Object.values(countryData7Days);

            const countryData30Days = <?php echo htmlspecialchars(json_encode($results30Days), ENT_QUOTES, 'UTF-8'); ?>;
            const countryLabels30Days = createCountryLinks(countryData30Days);
            const countryCounts30Days = Object.values(countryData30Days);

            function createChart(ctx, labels, data, backgroundColor, borderColor) {
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(labelObj => labelObj.label),
                        datasets: [{
                            label: 'Land',
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
                                    text: 'Land'
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

            createChart(document.getElementById('countryChartToday').getContext('2d'), countryLabelsToday, countryCountsToday, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createChart(document.getElementById('countryChartYesterday').getContext('2d'), countryLabelsYesterday, countryCountsYesterday, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createChart(document.getElementById('countryChart7Days').getContext('2d'), countryLabels7Days, countryCounts7Days, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createChart(document.getElementById('countryChart30Days').getContext('2d'), countryLabels30Days, countryCounts30Days, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>