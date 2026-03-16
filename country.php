<?php

declare(strict_types=1);

require_once 'includes/i18n.php';
$reportConfig = [
    'typeId' => 'country',
    'analyticsMethod' => 'getListenerAddWithCountry',
    'faqLinkType' => 'country',
    'pageTitle' => __('User Statistics by Country') . ' - SPCast Live',
    'pageDescription' => __('Analysis of user activities by countries. Detailed data for today, yesterday, the last 7 and 30 days.'),
    'keywords' => __('spcast live, spcast länderstatistik, nutzer nach land, echtzeitdaten, netzwerkstatistiken, länderverteilung'),
    'canonicalUrl' => 'https://live.spcast.eu/country.php',
    'headerTitle' => __('User Statistics by Country'),
    'headerDesc' => __('Analyze from which countries users in the SPCast network access radio stations. The statistics are anonymized and provide daily, weekly, and monthly evaluations.'),
    'sectionTitle' => __('Country Statistics for Today, Yesterday, 7 and the last 30 Days'),
    'chartTitles' => [
        'today' => __('Country Distribution for Today'),
        'yesterday' => __('Country Distribution for Yesterday'),
        '7days' => __('Country Distribution over the last 7 Days'),
        '30days' => __('Country Distribution over the last 30 Days')
    ],
    'chartLabel' => 'Land'
];

require_once 'includes/report_template.php';