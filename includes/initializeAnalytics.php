<?php

function initializeAnalytics()
{
    try {
        $client = new Google_Client();
        $client->setAuthConfig('../service-account.json');
        $client->addScope(Google_Service_AnalyticsData::ANALYTICS);
        
        return new Google_Service_AnalyticsData($client);
    } catch (Exception $e) {
        error_log('Analytics initialization failed: ' . $e->getMessage());
        throw $e;
    }
}

