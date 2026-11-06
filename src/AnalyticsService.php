<?php
declare(strict_types=1);

namespace App;

use Throwable;

class AnalyticsService
{
    public function __construct(
        private readonly \Google_Service_AnalyticsData $analytics,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param list<array{name: string}> $dimensions
     */
    public function getRealTimeReport(string $propertyId, array $dimensions = []): mixed
    {
        try {
            $config = [
                'metrics' => [['name' => 'activeUsers']],
            ];
            if ($dimensions !== []) {
                $config['dimensions'] = $dimensions;
            }

            $body = new \Google_Service_AnalyticsData_RunRealtimeReportRequest($config);

            return $this->analytics->properties->runRealtimeReport($propertyId, $body);
        } catch (Throwable $e) {
            $this->logger->error('AnalyticsService Error (RealTime): ' . $e->getMessage());
            return null;
        }
    }

    public function getActiveUsersReport(string $propertyId, string $startDate, string $endDate = 'yesterday'): mixed
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

    public function getWeeklyReport(string $propertyId): mixed
    {
        return $this->getActiveUsersReport($propertyId, '7daysAgo');
    }

    public function getMonthlyReport(string $propertyId): mixed
    {
        return $this->getActiveUsersReport($propertyId, '30daysAgo');
    }

    public function getListenerAddReport(
        string $propertyId,
        string $customEventName,
        string $startDate,
        string $endDate,
        string $logIdentifier
    ): mixed {
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
                            'matchType' => 'EXACT',
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

    /**
     * @return array<string, int>
     */
    public function processListenerAddReport(mixed $response): array
    {
        $results = [];
        if ($response && method_exists($response, 'getRows')) {
            $rows = $response->getRows();
            if (is_iterable($rows)) {
                foreach ($rows as $row) {
                    $item = (string) $row->getDimensionValues()[1]->getValue();
                    $count = (int) $row->getMetricValues()[0]->getValue();
                    if ($item !== '(not set)' && $item !== '') {
                        $results[$item] = ($results[$item] ?? 0) + $count;
                    }
                }
            }
        }

        arsort($results);

        return $results;
    }

    public function getCachedData(string $cacheFile, callable $fetchCallback, int $cacheTime = 45): ?array
    {
        $dir = dirname($cacheFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create analytics cache directory.');
        }

        $lock = fopen($cacheFile . '.lock', 'c+');
        if ($lock === false) {
            throw new \RuntimeException('Unable to open analytics cache lock.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock analytics cache.');
            }

            clearstatcache(true, $cacheFile);
            $cached = null;
            $modified = false;
            if (is_file($cacheFile)) {
                $raw = file_get_contents($cacheFile);
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $cached = $decoded;
                    }
                }
                $modified = filemtime($cacheFile);
            }

            if ($cached !== null && is_int($modified) && (time() - $modified) < $cacheTime) {
                return $cached;
            }

            rewind($lock);
            $retryAfter = (int) trim((string) stream_get_contents($lock));
            if ($retryAfter > time()) {
                return $cached;
            }

            $data = $fetchCallback();
            if ($data === null) {
                rewind($lock);
                ftruncate($lock, 0);
                fwrite($lock, (string) (time() + min(5, max(1, $cacheTime))));
                return $cached;
            }

            $tempFile = null;
            try {
                $json = json_encode($data, JSON_THROW_ON_ERROR);
                $tempFile = tempnam($dir, '.spcast-cache-');
                if ($tempFile === false) {
                    throw new \RuntimeException('Unable to create temporary analytics cache file.');
                }
                if (file_put_contents($tempFile, $json, LOCK_EX) !== strlen($json) || !rename($tempFile, $cacheFile)) {
                    throw new \RuntimeException('Unable to write analytics cache file.');
                }
                rewind($lock);
                ftruncate($lock, 0);
            } catch (Throwable $e) {
                $this->logger->error('Analytics cache write failed: ' . $e->getMessage());
            } finally {
                if (is_string($tempFile) && is_file($tempFile)) {
                    unlink($tempFile);
                }
            }

            return $data;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getStandardReportData(
        string $cachePrefix,
        string $propertyId,
        int $cacheTime = 45,
        string $cacheDir = 'cache/'
    ): array {
        $normalizedPrefix = $cachePrefix |> trim(...) |> strtolower(...);
        if (preg_match('/^[a-z0-9_]+$/', $normalizedPrefix) !== 1) {
            throw new \InvalidArgumentException('Invalid report cache prefix.');
        }
        $normalizedDir = rtrim($cacheDir, '/') . '/';

        $periods = [
            'today' => 'today',
            'yesterday' => 'yesterday',
            '7days' => '7daysAgo',
            '30days' => '30daysAgo',
        ];

        $results = [];

        foreach ($periods as $key => $startDate) {
            $cacheFile = $normalizedDir . "analytics_{$normalizedPrefix}_cache_{$key}.json";

            $cached = $this->getCachedData(
                $cacheFile,
                function () use ($propertyId, $startDate, $normalizedPrefix): ?array {
                    $customEventKey = 'customEvent:' . $normalizedPrefix;
                    $logIdentifier = ucfirst($normalizedPrefix);

                    $response = $this->getListenerAddReport(
                        $propertyId,
                        $customEventKey,
                        $startDate,
                        'today',
                        $logIdentifier
                    );

                    if ($response === null) {
                        return null;
                    }

                    return $this->processListenerAddReport($response);
                },
                $cacheTime
            );

            $normalizedMap = [];
            if (is_array($cached)) {
                foreach ($cached as $label => $val) {
                    $normalizedMap[(string) $label] = (int) $val;
                }
                arsort($normalizedMap);
            }

            $results[$key] = $normalizedMap;
        }

        return $results;
    }

    /**
     * @param array<string, int> $items
     * @return array{totalEvents: int, uniqueCount: int, topItem: ?array{label: string, count: int}}
     */
    public function summarizePeriodRanking(array $items): array
    {
        if ($items === []) {
            return [
                'totalEvents' => 0,
                'uniqueCount' => 0,
                'topItem' => null,
            ];
        }

        $topLabel = (string) array_key_first($items);
        $topCount = (int) array_first($items);

        return [
            'totalEvents' => (int) array_sum($items),
            'uniqueCount' => count($items),
            'topItem' => [
                'label' => $topLabel,
                'count' => $topCount,
            ],
        ];
    }

    /**
     * @return array{
     *   activeUsers: int,
     *   timestamp: int,
     *   hourly: array{labels: list<string>, values: list<int>},
     *   minutes: array{labels: list<string>, values: list<int>},
     *   weekly: array{labels: list<string>, values: list<int>},
     *   monthly: array{labels: list<string>, values: list<int>}
     * }
     */
    public function getInOutReportData(
        string $type,
        string $propertyId,
        string $cacheDir = 'cache/',
        int $realtimeTtl = 45
    ): array {
        $normalizedType = $type |> trim(...) |> strtolower(...);
        if ($normalizedType !== 'in' && $normalizedType !== 'out') {
            throw new \InvalidArgumentException('Invalid stream report type.');
        }
        $normalizedDir = rtrim($cacheDir, '/') . '/';

        $realtimeState = $this->fetchRealtimeAndHistory($normalizedType, $propertyId, $normalizedDir, $realtimeTtl);

        $minutesFile = $normalizedDir . "analytics_minutes_{$normalizedType}.json";
        $minutesMap = $this->getCachedData(
            $minutesFile,
            function () use ($propertyId): ?array {
                $response = $this->getRealTimeReport($propertyId, [['name' => 'minutesAgo']]);
                if ($response === null || !method_exists($response, 'getRows')) {
                    return null;
                }
                $rows = $response->getRows();
                if (!is_iterable($rows)) {
                    return [];
                }
                $map = [];
                foreach ($rows as $row) {
                    $minuteAgo = (int) $row->getDimensionValues()[0]->getValue();
                    $users = (int) $row->getMetricValues()[0]->getValue();
                    if ($minuteAgo >= 0 && $minuteAgo < 30) {
                        $map[$minuteAgo] = ($map[$minuteAgo] ?? 0) + $users;
                    }
                }
                return $map;
            },
            $realtimeTtl
        ) ?? [];

        $minuteLabels = [];
        $minuteValues = [];
        for ($m = 29; $m >= 0; $m--) {
            $minuteLabels[] = $m === 0 ? '0m' : "-{$m}m";
            $minuteValues[] = (int) ($minutesMap[$m] ?? $minutesMap[(string) $m] ?? 0);
        }

        $weeklyFile = $normalizedDir . "analytics_weekly_{$normalizedType}.json";
        $weeklyRaw = $this->getCachedData(
            $weeklyFile,
            fn (): ?array => $this->extractDailyActiveUsers($this->getWeeklyReport($propertyId)),
            86400
        ) ?? [];

        $monthlyFile = $normalizedDir . "analytics_monthly_{$normalizedType}.json";
        $monthlyRaw = $this->getCachedData(
            $monthlyFile,
            fn (): ?array => $this->extractDailyActiveUsers($this->getMonthlyReport($propertyId)),
            86400
        ) ?? [];

        $hourlyLabels = [];
        $hourlyValues = [];
        for ($h = 0; $h <= 23; $h++) {
            $hourlyLabels[] = sprintf('%02d:00', $h);
            $hourlyValues[] = (int) ($realtimeState['hours'][$h] ?? 0);
        }

        return [
            'activeUsers' => $realtimeState['activeUsers'],
            'timestamp' => $realtimeState['timestamp'],
            'hourly' => [
                'labels' => $hourlyLabels,
                'values' => $hourlyValues,
            ],
            'minutes' => [
                'labels' => $minuteLabels,
                'values' => $minuteValues,
            ],
            'weekly' => $this->buildDateSeries(7, $weeklyRaw),
            'monthly' => $this->buildDateSeries(30, $monthlyRaw),
        ];
    }

    /**
     * @return array{inbound: int, outbound: int, netFlow: int, timestamp: int, ttl: int}
     */
    public function getLivePulseSummary(
        string $inboundPropertyId,
        string $outboundPropertyId,
        string $cacheDir = 'cache/',
        int $ttl = 45
    ): array {
        $normalizedDir = rtrim($cacheDir, '/') . '/';
        $in = $this->fetchRealtimeAndHistory('in', $inboundPropertyId, $normalizedDir, $ttl);
        $out = $this->fetchRealtimeAndHistory('out', $outboundPropertyId, $normalizedDir, $ttl);

        return [
            'inbound' => $in['activeUsers'],
            'outbound' => $out['activeUsers'],
            'netFlow' => $in['activeUsers'] - $out['activeUsers'],
            'timestamp' => max($in['timestamp'], $out['timestamp']),
            'ttl' => $ttl,
        ];
    }

    /**
     * @return array{activeUsers: int, timestamp: int, hours: array<int, int>}
     */
    private function fetchRealtimeAndHistory(
        string $normalizedType,
        string $propertyId,
        string $normalizedDir,
        int $ttl
    ): array {
        $cacheFile = $normalizedDir . "analytics_cache_{$normalizedType}.json";
        $historyFile = $normalizedDir . "analytics_history_{$normalizedType}.json";
        $todayDate = date('Y-m-d');

        $data = $this->getCachedData(
            $cacheFile,
            function () use ($historyFile, $propertyId, $todayDate): ?array {
                $response = $this->getRealTimeReport($propertyId);
                if ($response === null || !method_exists($response, 'getRows')) {
                    return null;
                }

                $rows = $response->getRows();
                $activeUsers = 0;
                if (is_array($rows) && $rows !== []) {
                    $firstRow = array_first($rows);
                    $activeUsers = (int) $firstRow->getMetricValues()[0]->getValue();
                }

                $payload = [
                    'activeUsers' => $activeUsers,
                    'timestamp' => time(),
                ];

                $hours = $this->readTodayHours($historyFile, $todayDate);
                $currentHour = (int) date('G');
                $hours[$currentHour] = $activeUsers;

                $dir = dirname($historyFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents(
                    $historyFile,
                    json_encode(['date' => $todayDate, 'hours' => $hours]),
                    LOCK_EX
                );

                return $payload;
            },
            $ttl
        );

        $hours = $this->readTodayHours($historyFile, $todayDate);

        return [
            'activeUsers' => (int) ($data['activeUsers'] ?? 0),
            'timestamp' => (int) ($data['timestamp'] ?? time()),
            'hours' => $hours,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function readTodayHours(string $historyFile, string $todayDate): array
    {
        if (!file_exists($historyFile)) {
            return [];
        }

        $raw = file_get_contents($historyFile);
        if (!is_string($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['date'], $decoded['hours']) && is_array($decoded['hours'])) {
            if ($decoded['date'] !== $todayDate) {
                return [];
            }
            $source = $decoded['hours'];
        } else {
            // Backward compatibility with legacy flat hour map
            $source = $decoded;
        }

        $hours = [];
        foreach ($source as $hour => $val) {
            $hourInt = (int) $hour;
            if ($hourInt >= 0 && $hourInt <= 23 && is_numeric($val)) {
                $hours[$hourInt] = (int) $val;
            }
        }

        return $hours;
    }

    /**
     * @return ?array<string, int>
     */
    private function extractDailyActiveUsers(mixed $response): ?array
    {
        if ($response === null || !method_exists($response, 'getRows')) {
            return null;
        }

        $rows = $response->getRows();
        if (!is_iterable($rows)) {
            return [];
        }

        $series = [];
        foreach ($rows as $row) {
            $date = (string) $row->getDimensionValues()[0]->getValue();
            $activeUsers = (int) $row->getMetricValues()[0]->getValue();
            $series[$date] = $activeUsers;
        }

        return $series;
    }

    /**
     * @param array<string, mixed> $dateMap
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildDateSeries(int $days, array $dateMap): array
    {
        $labels = [];
        $values = [];
        for ($i = $days; $i > 0; $i--) {
            $timestamp = strtotime("-{$i} days");
            $key = date('Ymd', $timestamp);
            $labels[] = date('d.m', $timestamp);
            $values[] = (int) ($dateMap[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
