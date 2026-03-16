<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'region',
    'analyticsMethod' => 'getListenerAddWithRegion',
    'faqLinkType' => 'region',
    'pageTitle' => __('User Statistics by Region - SPCast Live'),
    'pageDescription' => __('Shows user activities by region. Detailed statistics for various time periods, daily and weekly.'),
    'keywords' => __('spcast live, spcast bundesländerstatistik, nutzer nach bundesland, netzwerkdaten, echtzeitstatistiken'),
    'canonicalUrl' => 'https://live.spcast.eu/region.php',
    'headerTitle' => __('User Statistics by Region'),
    'headerDesc' => __('View anonymized data on user activities in the SPCast network by region. These statistics show how usage is distributed geographically, based on daily and weekly evaluations.'),
    'sectionTitle' => __('Region Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('Distribution by Region for Today'),
        'yesterday' => __('Distribution by Region for Yesterday'),
        '7days' => __('Distribution by Region over the last 7 Days'),
        '30days' => __('Distribution by Region over the last 30 Days')
    ],
    'chartLabel' => 'Bundesland'
];

require_once 'includes/report_template.php';