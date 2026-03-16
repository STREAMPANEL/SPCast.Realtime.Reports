<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'os',
    'analyticsMethod' => 'getListenerAddWithOS',
    'faqLinkType' => 'os',
    'pageTitle' => __('Operating System Statistics - SPCast Live'),
    'pageDescription' => __('Overview of the operating systems used by users. Live data and statistics for various periods.'),
    'keywords' => __('spcast live, spcast betriebssysteme, nutzerplattformen, os statistiken, echtzeitdaten, netzwerkplattformen'),
    'canonicalUrl' => 'https://live.spcast.eu/os.php',
    'headerTitle' => __('Operating System Statistics'),
    'headerDesc' => __('Statistics on the operating systems used by users in the SPCast network. Anonymized data provides insights into technical preferences and trends across various periods.'),
    'sectionTitle' => __('Operating System Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('Operating System Distribution for Today'),
        'yesterday' => __('Operating System Distribution for Yesterday'),
        '7days' => __('Operating System Distribution over the last 7 Days'),
        '30days' => __('Operating System Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Betriebssystem'
];

require_once 'includes/report_template.php';