<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'ipversion',
    'analyticsMethod' => 'getListenerAddWithIPVersion',
    'faqLinkType' => 'ipversion',
    'pageTitle' => __('IP Version Statistics - SPCast Live'),
    'pageDescription' => __('Shows the usage of IPv4 and IPv6 by users in the network. Real-time data and statistics for various periods.'),
    'keywords' => __('spcast live, spcast ip-version, ipv4, ipv6, ip statistiken, nutzerdaten, netzwerkprotokolle'),
    'canonicalUrl' => 'https://live.spcast.eu/ipversion.php',
    'headerTitle' => __('IP Version Statistics'),
    'headerDesc' => 'Anonymisierte Daten zur Nutzung von IPv4 und IPv6 im SPCast-Netzwerk. Diese Statistiken geben Aufschluss über die Verteilung der IP-Versionen innerhalb der Radiostationen.',
    'sectionTitle' => __('IP Version Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('IP Version Distribution for Today'),
        'yesterday' => __('IP Version Distribution for Yesterday'),
        '7days' => __('IP Version Distribution over the last 7 Days'),
        '30days' => __('IP Version Distribution over the last 30 Days')
    ],
    'chartLabel' => 'IP-Version'
];

require_once 'includes/report_template.php';