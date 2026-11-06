<?php
declare(strict_types=1);

require_once 'includes/config.php';
require_once 'includes/i18n.php';

$reportConfig = [
    'typeId' => 'mount',
    'gaPropertyId' => GA_PROPERTY_ID_GENERAL,
    'pageTitle' => __('Mountpoint Statistics - SPCast Live'),
    'pageDescription' => __('Detailed statistics on the mountpoints used in the network. Analyses available for various periods.'),
    'keywords' => __('spcast live, spcast mountpoints, nutzerdaten, echtzeitstatistiken, netzwerkanalysen, mountpoint statistiken'),
    'canonicalUrl' => 'https://live.spcast.eu/mount.php',
    'headerTitle' => __('Mountpoint Statistics'),
    'headerDesc' => __('Get detailed, anonymized data on the mountpoints used in the SPCast network. These statistics show general trends in radio station usage.'),
    'sectionTitle' => __('Mountpoint Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('Mountpoint Distribution for Today'),
        'yesterday' => __('Mountpoint Distribution for Yesterday'),
        '7days' => __('Mountpoint Distribution over the last 7 Days'),
        '30days' => __('Mountpoint Distribution over the last 30 Days'),
    ],
    'chartLabel' => __('Mountpoint'),
];

require_once 'includes/report_template.php';
