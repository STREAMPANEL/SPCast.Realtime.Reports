<?php

declare(strict_types=1);

// includes/report_template.php
require_once 'includes/i18n.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'vendor/autoload.php';

$cacheDir = 'cache/';
require_once 'includes/initializeAnalytics.php';
ensureSecureCacheDirectory($cacheDir);
require_once 'includes/config.php';

$logger = \App\Logger::createDefault();
$analyticsClient = initializeAnalytics();
$analyticsService = new \App\AnalyticsService($analyticsClient, $logger);

$reportData = $analyticsService->getStandardReportData($reportConfig['typeId'], GA_PROPERTY_ID_GENERAL);
$resultsToday = $reportData['today'];
$resultsYesterday = $reportData['yesterday'];
$results7Days = $reportData['7days'];
$results30Days = $reportData['30days'];
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang ?? 'en'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $reportConfig['pageTitle']; ?></title>
    <meta name="description" content="<?php echo $reportConfig['pageDescription']; ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($reportConfig['keywords'], ENT_QUOTES); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($reportConfig['canonicalUrl'], ENT_QUOTES); ?>" />
    <?php require_once "includes/head.php"; ?>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="text-center"><?php echo $reportConfig['headerTitle']; ?></h1>
            <p><?php echo $reportConfig['headerDesc']; ?></p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $reportConfig['sectionTitle']; ?></h3>
                        <p><?php echo __('Next update in'); ?> <span id="countdown">45</span> <?php echo __('seconds'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center"><?php echo $reportConfig['chartTitles']['today']; ?></h4>
                <canvas id="chartToday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center"><?php echo $reportConfig['chartTitles']['yesterday']; ?></h4>
                <canvas id="chartYesterday"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center"><?php echo $reportConfig['chartTitles']['7days']; ?></h4>
                <canvas id="chart7Days"></canvas>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <h4 class="text-center"><?php echo $reportConfig['chartTitles']['30days']; ?></h4>
                <canvas id="chart30Days"></canvas>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Output JSON with secure flags to prevent Stored XSS
            const dataToday = <?php echo json_encode($resultsToday, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const labelsToday = createFaqLinks(dataToday, '<?php echo $reportConfig["faqLinkType"]; ?>');
            const countsToday = Object.values(dataToday);

            const dataYesterday = <?php echo json_encode($resultsYesterday, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const labelsYesterday = createFaqLinks(dataYesterday, '<?php echo $reportConfig["faqLinkType"]; ?>');
            const countsYesterday = Object.values(dataYesterday);

            const data7Days = <?php echo json_encode($results7Days, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const labels7Days = createFaqLinks(data7Days, '<?php echo $reportConfig["faqLinkType"]; ?>');
            const counts7Days = Object.values(data7Days);

            const data30Days = <?php echo json_encode($results30Days, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const labels30Days = createFaqLinks(data30Days, '<?php echo $reportConfig["faqLinkType"]; ?>');
            const counts30Days = Object.values(data30Days);

            const chartLabel = '<?php echo $reportConfig["chartLabel"]; ?>';

            createBarChart(document.getElementById('chartToday').getContext('2d'), labelsToday, countsToday, chartLabel, 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            createBarChart(document.getElementById('chartYesterday').getContext('2d'), labelsYesterday, countsYesterday, chartLabel, 'rgba(153, 102, 255, 0.6)', 'rgba(153, 102, 255, 1)');
            createBarChart(document.getElementById('chart7Days').getContext('2d'), labels7Days, counts7Days, chartLabel, 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
            createBarChart(document.getElementById('chart30Days').getContext('2d'), labels30Days, counts30Days, chartLabel, 'rgba(75, 192, 192, 0.6)', 'rgba(75, 192, 192, 1)');
        });
    </script>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>
