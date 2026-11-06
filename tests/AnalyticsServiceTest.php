<?php
declare(strict_types=1);

namespace Tests;

use App\AnalyticsService;
use Google\Service\AnalyticsData\RunReportRequest;

class AnalyticsServiceTest extends TestCase
{
    private $analyticsMock;
    private $propertiesMock;
    private AnalyticsService $service;

    protected function setUp(): void
    {
        $this->propertiesMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['runRealtimeReport', 'runReport'])
            ->getMock();

        $this->analyticsMock = $this->getMockBuilder(\Google_Service_AnalyticsData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->analyticsMock->properties = $this->propertiesMock;

        $this->service = new AnalyticsService($this->analyticsMock, \App\Logger::createDefault());
    }

    public function testGetRealTimeReport(): void
    {
        $propertyId = 'properties/12345';
        $this->propertiesMock->expects($this->once())
            ->method('runRealtimeReport')
            ->with(
                $this->equalTo($propertyId),
                $this->callback(function ($request) {
                    if (!$request instanceof \Google_Service_AnalyticsData_RunRealtimeReportRequest) {
                        return false;
                    }
                    if (property_exists($request, 'config') && is_array($request->config)) {
                        return $request->config['metrics'][0]['name'] === 'activeUsers';
                    }
                    if (method_exists($request, 'getMetrics')) {
                        $metrics = $request->getMetrics();
                        $metric = $metrics[0];
                        if (is_object($metric)) {
                            return $metric->getName() === 'activeUsers' || (isset($metric->name) && $metric->name === 'activeUsers');
                        } elseif (is_array($metric)) {
                            return $metric['name'] === 'activeUsers';
                        }
                    }
                    return false;
                })
            )
            ->willReturn('report_result');

        $result = $this->service->getRealTimeReport($propertyId);
        $this->assertEquals('report_result', $result);
    }

    public function testGetActiveUsersReport(): void
    {
        $propertyId = 'properties/12345';
        $this->propertiesMock->expects($this->once())
            ->method('runReport')
            ->with($this->equalTo($propertyId), $this->isInstanceOf(RunReportRequest::class))
            ->willReturn('report_result');

        $result = $this->service->getActiveUsersReport($propertyId, '7daysAgo');
        $this->assertEquals('report_result', $result);
    }

    public function testGetActiveUsersReportError(): void
    {
        $propertyId = 'properties/12345';
        $this->propertiesMock->expects($this->once())
            ->method('runReport')
            ->willThrowException(new \Exception('Simulated Error'));

        $this->assertNull($this->service->getActiveUsersReport($propertyId, '7daysAgo'));
    }

    public function testGetWeeklyReport(): void
    {
        $propertyId = 'properties/12345';

        $this->propertiesMock->expects($this->once())
            ->method('runReport')
            ->willReturn('report_result');

        $result = $this->service->getWeeklyReport($propertyId);
        $this->assertEquals('report_result', $result);
    }

    public function testGetMonthlyReport(): void
    {
        $propertyId = 'properties/12345';
        $this->propertiesMock->expects($this->once())
            ->method('runReport')
            ->willReturn('report_result');

        $result = $this->service->getMonthlyReport($propertyId);
        $this->assertEquals('report_result', $result);
    }

    public function testGetRealTimeReportError(): void
    {
        $this->propertiesMock->expects($this->once())
            ->method('runRealtimeReport')
            ->willThrowException(new \Exception('Simulated Error'));

        $this->assertNull($this->service->getRealTimeReport('123'));
    }

    public function testGetMonthlyReportError(): void
    {
        $this->propertiesMock->expects($this->once())
            ->method('runReport')
            ->willThrowException(new \Exception('Simulated Error'));

        $this->assertNull($this->service->getMonthlyReport('123'));
    }

    public function testGetCachedData(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'test_cache');

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        if (file_exists($cacheFile . '.lock')) {
            unlink($cacheFile . '.lock');
        }

        $data = ['key' => 'value'];
        $fetchCallback = function () use ($data) {
            return $data;
        };

        $result = $this->service->getCachedData($cacheFile, $fetchCallback);
        $this->assertEquals($data, $result);
        $this->assertFileExists($cacheFile);
        $this->assertJsonStringEqualsJsonString((string) json_encode($data), (string) file_get_contents($cacheFile));

        $cachedData = ['key' => 'cached'];
        file_put_contents($cacheFile, json_encode($cachedData));

        $fetchCallback = function () {
            $this->fail('Callback should not be called when cache is fresh');
        };

        $result = $this->service->getCachedData($cacheFile, $fetchCallback);
        $this->assertEquals($cachedData, $result);

        touch($cacheFile, time() - 100);
        clearstatcache(true, $cacheFile);

        $newData = ['key' => 'new'];
        $fetchCallback = function () use ($newData) {
            return $newData;
        };

        $result = $this->service->getCachedData($cacheFile, $fetchCallback, 45);
        $this->assertEquals($newData, $result);
        $this->assertJsonStringEqualsJsonString((string) json_encode($newData), (string) file_get_contents($cacheFile));

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        if (file_exists($cacheFile . '.lock')) {
            unlink($cacheFile . '.lock');
        }
    }

    public function testGetCachedDataDoesNotCacheNull(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'test_cache_null');

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        $fetchCallback = function () {
            return null;
        };

        $result = $this->service->getCachedData($cacheFile, $fetchCallback);
        $this->assertNull($result);
        $this->assertFileDoesNotExist($cacheFile);
        if (file_exists($cacheFile . '.lock')) {
            unlink($cacheFile . '.lock');
        }
    }

    public function testGetCachedDataReturnsStaleDataAndDelaysRetryAfterFailure(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'test_cache_stale');
        $this->assertIsString($cacheFile);
        file_put_contents($cacheFile, json_encode(['value' => 'stale']));
        touch($cacheFile, time() - 100);

        $attempts = 0;
        $fetchCallback = static function () use (&$attempts): ?array {
            $attempts++;
            return null;
        };

        $this->assertSame(['value' => 'stale'], $this->service->getCachedData($cacheFile, $fetchCallback));
        $this->assertSame(['value' => 'stale'], $this->service->getCachedData($cacheFile, $fetchCallback));
        $this->assertSame(1, $attempts);

        unlink($cacheFile);
        unlink($cacheFile . '.lock');
    }

    public function testStandardReportPreservesStalePeriodsWhenAnalyticsFails(): void
    {
        $cacheDir = sys_get_temp_dir() . '/spcast-standard-stale-' . bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($cacheDir, 0700));
        $periods = ['today', 'yesterday', '7days', '30days'];

        try {
            foreach ($periods as $period) {
                $cacheFile = "$cacheDir/analytics_city_cache_$period.json";
                file_put_contents($cacheFile, json_encode(['Berlin' => 9]));
                touch($cacheFile, time() - 100);
            }

            $this->propertiesMock->expects($this->exactly(4))
                ->method('runReport')
                ->willThrowException(new \RuntimeException('Simulated provider outage'));

            $report = $this->service->getStandardReportData('city', 'properties/12345', 45, $cacheDir);
            foreach ($periods as $period) {
                $this->assertSame(['Berlin' => 9], $report[$period]);
            }
        } finally {
            foreach ($periods as $period) {
                foreach (["$cacheDir/analytics_city_cache_$period.json", "$cacheDir/analytics_city_cache_$period.json.lock"] as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($cacheDir);
        }
    }

    public function testProcessListenerAddReport(): void
    {
        $responseMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRows'])
            ->getMock();

        $rows = [];
        $rows[] = $this->createMockRow('listener_add', 'Windows', 10);
        $rows[] = $this->createMockRow('listener_add', 'Linux', 5);
        $rows[] = $this->createMockRow('listener_add', 'Windows', 3);
        $rows[] = $this->createMockRow('listener_add', '(not set)', 20);

        $responseMock->method('getRows')->willReturn($rows);

        $result = $this->service->processListenerAddReport($responseMock);

        $expected = [
            'Windows' => 13,
            'Linux' => 5,
        ];

        $this->assertEquals($expected, $result);
    }

    private function createMockRow(string $eventName, string $customValue, int|string $count): object
    {
        $row = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getDimensionValues', 'getMetricValues'])
            ->getMock();

        $dimValue1 = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
        $dimValue1->method('getValue')->willReturn($eventName);

        $dimValue2 = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
        $dimValue2->method('getValue')->willReturn($customValue);

        $row->method('getDimensionValues')->willReturn([$dimValue1, $dimValue2]);

        $metricValue = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
        $metricValue->method('getValue')->willReturn($count);

        $row->method('getMetricValues')->willReturn([$metricValue]);

        return $row;
    }
}
