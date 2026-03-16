<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'browser',
    'analyticsMethod' => 'getListenerAddWithBrowser',
    'faqLinkType' => 'agent', // mapped to agent since createFaqLinks expects 'agent' for browser data
    'pageTitle' => __('Browser Statistics') . ' - SPCast Live',
    'pageDescription' => __('Statistics on the browsers used by users. Real-time data for daily, weekly, and monthly evaluations.'),
    'keywords' => __('spcast live, spcast browserstatistiken, browsernutzung, nutzerdaten, echtzeitstatistiken, netzwerkdaten'),
    'canonicalUrl' => 'https://live.spcast.eu/browser.php',
    'headerTitle' => __('Browser Statistics'),
    'headerDesc' => __('Anonymized data on the used browsers of the users in the SPCast network. These statistics help in analyzing technical preferences of users, based on real-time data.'),
    'sectionTitle' => __('Browser Statistics for Today, Yesterday, 7 and the last 30 Days'),
    'chartTitles' => [
        'today' => __('Browser Distribution for Today'),
        'yesterday' => __('Browser Distribution for Yesterday'),
        '7days' => __('Browser Distribution over the last 7 Days'),
        '30days' => __('Browser Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Browser'
];

require_once 'includes/report_template.php';