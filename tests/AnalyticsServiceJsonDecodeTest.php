<?php
declare(strict_types=1);

namespace Tests;

use App\AnalyticsService;

class AnalyticsServiceJsonDecodeTest extends TestCase
{
    private $analyticsMock;
    private AnalyticsService $service;

    protected function setUp(): void
    {
        $this->analyticsMock = $this->getMockBuilder(\Google_Service_AnalyticsData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new AnalyticsService($this->analyticsMock, \App\Logger::createDefault());
    }

    public function testGetCachedDataWithCorruptedJson(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'test_cache');
        file_put_contents($cacheFile, '');

        $fetchCallback = function () {
            return ['key' => 'fetched_data'];
        };

        $result = $this->service->getCachedData($cacheFile, $fetchCallback);
        $this->assertIsArray($result);
        $this->assertEquals(['key' => 'fetched_data'], $result);

        unlink($cacheFile);
    }
}
