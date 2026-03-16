<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'protocol',
    'analyticsMethod' => 'getListenerAddWithProtocol',
    'faqLinkType' => 'protocol',
    'pageTitle' => __('Protocol Statistics - SPCast Live'),
    'pageDescription' => __('Shows the protocols of user activities in the network. Statistics for daily, weekly, and monthly evaluations.'),
    'keywords' => __('spcast live, spcast protokollstatistiken, nutzerprotokoll, netzwerkprotokolle, echtzeitstatistiken, datenprotokolle'),
    'canonicalUrl' => 'https://live.spcast.eu/protocol.php',
    'headerTitle' => __('Protocol Statistics'),
    'headerDesc' => __('View anonymized protocol statistics of user activities in the SPCast network. This data provides insights into the usage of technical protocols for various periods.'),
    'sectionTitle' => __('Protocol Statistics for Today, Yesterday, 7 and 30 Days'),
    'chartTitles' => [
        'today' => __('Protocol Distribution for Today'),
        'yesterday' => __('Protocol Distribution for Yesterday'),
        '7days' => __('Protocol Distribution over the last 7 Days'),
        '30days' => __('Protocol Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Protokoll'
];

require_once 'includes/report_template.php';