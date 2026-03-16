<?php

declare(strict_types=1);

namespace App;

use Throwable;

class AnalyticsService
{
    private \Google_Service_AnalyticsData $analytics;
    private Logger $logger;

    public function __construct(\Google_Service_AnalyticsData $analytics, Logger $logger)
    {
        $this->analytics = $analytics;
        $this->logger = $logger;
    }

    public function getRealTimeReport($propertyId)
    {
        try {
            $body = new \Google_Service_AnalyticsData_RunRealtimeReportRequest([
                'metrics' => [['name' => 'activeUsers']],
            ]);

            return $this->analytics->properties->runRealtimeReport($propertyId, $body);
        } catch (Throwable $e) {
            $this->logger->error('AnalyticsService Error (RealTime): ' . $e->getMessage());
            return null;
        }
    }

    public function getActiveUsersReport($propertyId, $startDate, $endDate = 'yesterday')
    {
        try {
            $body = new \Google\Service\AnalyticsData\RunReportRequest([
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'metrics' => [
                    ['name' => 'activeUsers'],
                ],
                'dimensions' => [
                    ['name' => 'date'],
                ],
            ]);

            return $this->analytics->properties->runReport($propertyId, $body);
        } catch (Throwable $e) {
            $this->logger->error('AnalyticsService Error (ActiveUsers): ' . $e->getMessage());
            return null;
        }
    }

    public function getWeeklyReport($propertyId)
    {
        return $this->getActiveUsersReport($propertyId, '7daysAgo');
    }

    public function getMonthlyReport($propertyId)
    {
        return $this->getActiveUsersReport($propertyId, '30daysAgo');
    }

    /**
     * @param string $propertyId The GA4 property ID.
     * @param string $customEventName Parameter suffix like 'customEvent:city'.
     * @param string $startDate Start period.
     * @param string $endDate End period.
     * @param string $logIdentifier Identifier used in logs.
     * @return \Google\Service\AnalyticsData\RunReportResponse|null
     */
    public function getListenerAddReport($propertyId, $customEventName, $startDate, $endDate, $logIdentifier)
    {
        try {
            $body = new \Google\Service\AnalyticsData\RunReportRequest([
                'dimensions' => [
                    ['name' => 'eventName'],
                    ['name' => $customEventName],
                ],
                'metrics' => [
                    ['name' => 'eventCount'],
                ],
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName' => 'eventName',
                        'stringFilter' => [
                            'value' => 'listener_add',
                            'matchType' => 'EXACT'
                        ],
                    ],
                ],
            ]);

            return $this->analytics->properties->runReport($propertyId, $body);
        } catch (Throwable $e) {
            $this->logger->error("AnalyticsService Error ($logIdentifier): " . $e->getMessage());
            return null;
        }
    }

    public function processListenerAddReport($response)
    {
        $results = [];
        if ($response && $response->getRows()) {
            foreach ($response->getRows() as $row) {
                // Dimension 0 is eventName, Dimension 1 is the custom attribute (e.g. Browser, OS, etc.)
                $item = $row->getDimensionValues()[1]->getValue();
                $count = $row->getMetricValues()[0]->getValue();
                if ($item !== "(not set)") {
                    $results[$item] = isset($results[$item]) ? $results[$item] + $count : $count;
                }
            }
        }
        return $results;
    }

    /**
     * Retrieves data from the cache file if valid; otherwise executes the fetch callback and caches the result.
     *
     * @param string $cacheFile The path to the cache file.
     * @param callable $fetchCallback The callback function to fetch fresh data.
     * @param int $cacheTime The cache validity duration in seconds (default: 45).
     * @return array|null The data, or null on error.
     */
    public function getCachedData(string $cacheFile, callable $fetchCallback, int $cacheTime = 45)
    {
        // Return cached data if valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $data = $fetchCallback();
        if ($data !== null) {
            file_put_contents($cacheFile, json_encode($data));
        }
        return $data;
    }

    /**
     * Helper to fetch standard 4-period reports (today, yesterday, 7daysAgo, 30daysAgo)
     * 
     * @param string $cachePrefix Example: 'browser' for 'analytics_browser_cache'
     * @param string $propertyId GA4 Property ID
     * @param int $cacheTime Cache valid time in seconds
     * @return array Returns ['today' => [...], 'yesterday' => [...], '7days' => [...], '30days' => [...]]
     */
    public function getStandardReportData(string $cachePrefix, string $propertyId, int $cacheTime = 45): array
    {
        $cacheDir = 'cache/';
        $periods = [
            'today' => 'today',
            'yesterday' => 'yesterday',
            '7days' => '7daysAgo',
            '30days' => '30daysAgo'
        ];

        $results = [];

        foreach ($periods as $key => $startDate) {
            $cacheFile = $cacheDir . "analytics_{$cachePrefix}_cache_{$key}.json";

            $results[$key] = $this->getCachedData($cacheFile, function () use ($propertyId, $startDate, $cachePrefix) {
                $customEventKey = 'customEvent:' . $cachePrefix;
                $logIdentifier = ucfirst($cachePrefix);

                $response = $this->getListenerAddReport($propertyId, $customEventKey, $startDate, 'today', $logIdentifier);
                return $this->processListenerAddReport($response);
            }, $cacheTime);
        }

        return $results;
    }
}
