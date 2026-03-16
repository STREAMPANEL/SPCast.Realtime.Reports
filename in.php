<?php

declare(strict_types=1);

require_once 'includes/config.php';
require_once 'includes/i18n.php';

$inoutConfig = [
    'type' => 'in',
    'gaPropertyId' => GA_PROPERTY_ID_INBOUND,
    'pageTitle' => __('Incoming User Statistics - SPCast Live'),
    'pageDescription' => __('Track all users who tuned into stations within the last 30 minutes. Live statistics and real-time insights.'),
    'keywords' => __('spcast live, spcast nutzerstatistik, eingehende nutzer, sender einschalten, echtzeitdaten, netzwerkaktivitäten'),
    'canonicalUrl' => 'https://live.spcast.eu/in.php',
    'headerTitle' => __('Incoming User Statistics'),
    'headerDesc' => __('This statistic shows anonymized data on users who tuned into a station in the SPCast network within the last 30 minutes. Analyze trends and general usage activities.'),
    'cardTitle' => __('Tuned in over the last 30 minutes'),
    'cardDesc' => __('Users who tuned in to a station in the last 30 minutes.'),
    'chartLabels' => [
        'realtime' => __('Tuned in per 30 minutes'),
        'realtimeY' => __('Tuned in per 30 minutes'),
        'weekly' => __('Tuned in over the last 7 days'),
        'monthly' => __('Tuned in over the last 30 days')
    ]
];

require_once 'includes/inout_template.php';
