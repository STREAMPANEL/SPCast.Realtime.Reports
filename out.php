<?php

declare(strict_types=1);

require_once 'includes/config.php';
require_once 'includes/i18n.php';

$inoutConfig = [
    'type' => 'out',
    'gaPropertyId' => GA_PROPERTY_ID_OUTBOUND,
    'pageTitle' => __('Outgoing User Statistics - SPCast Live'),
    'pageDescription' => __('Shows the users who tuned out of a station in the network within the last 30 minutes. Real-time and live data available.'),
    'keywords' => __('spcast live, spcast nutzerstatistik, ausgehende nutzer, sender ausschalten, echtzeitdaten, netzwerkaktivitäten'),
    'canonicalUrl' => 'https://live.spcast.eu/out.php',
    'headerTitle' => __('Outgoing User Statistics'),
    'headerDesc' => __('Anonymized statistics on users who tuned out of a station in the SPCast network within the last 30 minutes. A valuable source of analysis for general trends in the network.'),
    'cardTitle' => __('Tuned out over the last 30 minutes'),
    'cardDesc' => __('Users who tuned out of a station in the last 30 minutes.'),
    'chartLabels' => [
        'realtime' => __('Tuned out per 30 minutes'),
        'realtimeY' => __('Tuned out per 30 minutes'),
        'weekly' => __('Tuned out over the last 7 days'),
        'monthly' => __('Tuned out over the last 30 days')
    ]
];

require_once 'includes/inout_template.php';