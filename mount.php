<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'mount',
    'analyticsMethod' => 'getListenerAddWithMount',
    'faqLinkType' => 'mount',
    'pageTitle' => __('Mountpoint Statistics - SPCast Live'),
    'pageDescription' => 'Detaillierte Statistiken über die genutzten Mountpoints im Netzwerk. Analysen für verschiedene Zeiträume verfügbar.',
    'keywords' => __('spcast live, spcast mountpoints, nutzerdaten, echtzeitstatistiken, netzwerkanalysen, mountpoint statistiken'),
    'canonicalUrl' => 'https://live.spcast.eu/mount.php',
    'headerTitle' => __('Mountpoint Statistics'),
    'headerDesc' => 'Erhalten Sie detaillierte, anonymisierte Daten zu den genutzten Mountpoints im SPCast-Netzwerk. Diese Statistiken zeigen allgemeine Trends zur Nutzung der Radiostationen.',
    'sectionTitle' => __('Mountpoint Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('Mountpoint Distribution for Today'),
        'yesterday' => __('Mountpoint Distribution for Yesterday'),
        '7days' => __('Mountpoint Distribution over the last 7 Days'),
        '30days' => __('Mountpoint Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Mountpoint'
];

require_once 'includes/report_template.php';