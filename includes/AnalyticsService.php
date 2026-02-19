<?php

class AnalyticsService
{
    private $analytics;

    public function __construct($analytics)
    {
        $this->analytics = $analytics;
    }

    public function getRealTimeReport($propertyId)
    {
        try {
            $body = new Google_Service_AnalyticsData_RunRealtimeReportRequest([
                'metrics' => [['name' => 'activeUsers']],
            ]);

            return $this->analytics->properties->runRealtimeReport($propertyId, $body);
        } catch (Throwable $e) {
            error_log('AnalyticsService Error (RealTime): ' . $e->getMessage());
            return null;
        }
    }

    public function getWeeklyReport($propertyId)
    {
        try {
            $body = new Google\Service\AnalyticsData\RunReportRequest([
                'dateRanges' => [
                    ['startDate' => '7daysAgo', 'endDate' => 'yesterday'],
                ],
                'metrics'    => [
                    ['name' => 'activeUsers'],
                ],
                'dimensions' => [
                    ['name' => 'date'],
                ],
            ]);

            return $this->analytics->properties->runReport($propertyId, $body);
        } catch (Throwable $e) {
            error_log('AnalyticsService Error (Weekly): ' . $e->getMessage());
            return null;
        }
    }

    public function getMonthlyReport($propertyId)
    {
        try {
            $body = new Google\Service\AnalyticsData\RunReportRequest([
                'dateRanges' => [
                    ['startDate' => '30daysAgo', 'endDate' => 'yesterday'],
                ],
                'metrics'    => [
                    ['name' => 'activeUsers'],
                ],
                'dimensions' => [
                    ['name' => 'date'],
                ],
            ]);

            return $this->analytics->properties->runReport($propertyId, $body);
        } catch (Throwable $e) {
            error_log('AnalyticsService Error (Monthly): ' . $e->getMessage());
            return null;
        }
    }

    public function getListenerAddWithCountry($propertyId, $startDate, $endDate = 'today')
    {
        try {
            $body = new Google_Service_AnalyticsData_RunReportRequest([
                'dimensions'      => [
                    ['name' => 'eventName'],
                    ['name' => 'customEvent:country'],
                ],
                'metrics'         => [
                    ['name' => 'eventCount'],
                ],
                'dateRanges'      => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName'    => 'eventName',
                        'stringFilter' => [
                            'value' => 'listener_add',
                        ],
                    ],
                ],
            ]);

            return $this->analytics->properties->runReport($propertyId, $body);
        } catch (Throwable $e) {
            error_log('AnalyticsService Error (Country): ' . $e->getMessage());
            return null;
        }
    }
}
