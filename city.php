<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'city',
    'analyticsMethod' => 'getListenerAddWithCity',
    'faqLinkType' => 'city',
    'pageTitle' => __('User Statistics by City - SPCast Live'),
    'pageDescription' => __('Detailed data on the origin of users at the city level. Statistics for today, the last 7 and 30 days.'),
    'keywords' => __('spcast live, spcast städtestatistik, nutzer nach stadt, echtzeitdaten, netzwerkstatistiken, städteanalyse'),
    'canonicalUrl' => 'https://live.spcast.eu/city.php',
    'headerTitle' => __('User Statistics by City'),
    'headerDesc' => __('Anonymized statistics on the usage of radio stations in the SPCast network, sorted by cities. Gain insights into the local distribution of activities.'),
    'sectionTitle' => __('City Statistics of users for today, yesterday, the last 7 and 30 days'),
    'chartTitles' => [
        'today' => __('City Distribution for Today'),
        'yesterday' => __('City Distribution for Yesterday'),
        '7days' => __('City Distribution over the last 7 Days'),
        '30days' => __('City Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Stadt'
];

require_once 'includes/report_template.php';