<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'port',
    'analyticsMethod' => 'getListenerAddWithPort',
    'faqLinkType' => 'port',
    'pageTitle' => __('Port Statistics - SPCast Live'),
    'pageDescription' => __('Statistics on the ports used by users in the network. Real-time data for daily and weekly analyses.'),
    'keywords' => __('spcast live, spcast ports, nutzerstatistik ports, netzwerkdaten, echtzeitanalysen, portstatistiken'),
    'canonicalUrl' => 'https://live.spcast.eu/port.php',
    'headerTitle' => __('Port Statistics'),
    'headerDesc' => __('Anonymized statistics on the ports used by users in the SPCast network. This data helps to identify general trends and technical preferences of radio stations.'),
    'sectionTitle' => __('Port Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('Port Distribution for Today'),
        'yesterday' => __('Port Distribution for Yesterday'),
        '7days' => __('Port Distribution over the last 7 Days'),
        '30days' => __('Port Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Port'
];

require_once 'includes/report_template.php';