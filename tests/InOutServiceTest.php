<?php
declare(strict_types=1);

namespace Tests;

use App\AnalyticsService;
use App\Logger;

class InOutServiceTest extends TestCase
{
    private $propertiesMock;
    private $analyticsMock;
    private AnalyticsService $service;
    private string $tempCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertiesMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['runRealtimeReport', 'runReport'])
            ->getMock();

        $this->analyticsMock = $this->getMockBuilder(\Google_Service_AnalyticsData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->analyticsMock->properties = $this->propertiesMock;

        $this->service = new AnalyticsService($this->analyticsMock, Logger::createDefault());
        $this->tempCacheDir = sys_get_temp_dir() . '/spcast_inout_test_' . uniqid() . '/';
        mkdir($this->tempCacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempCacheDir)) {
            foreach (glob($this->tempCacheDir . '*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempCacheDir);
        }
        parent::tearDown();
    }

    public function testProcessListenerAddReportCastsStringsAndSortsDescending(): void
    {
        $response = $this->createReportResponse([
            ['listener_add', 'Firefox', '12'],
            ['listener_add', 'Chrome', '45'],
            ['listener_add', 'Safari', '20'],
        ]);

        $result = $this->service->processListenerAddReport($response);

        $this->assertSame(['Chrome', 'Safari', 'Firefox'], array_keys($result));
        $this->assertSame(45, $result['Chrome']);
        $this->assertSame(20, $result['Safari']);
        $this->assertSame(12, $result['Firefox']);
    }

    public function testSummarizePeriodRankingExtractsTopItem(): void
    {
        $summary = $this->service->summarizePeriodRanking([
            'Germany' => 120,
            'Austria' => 30,
            'Switzerland' => 15,
        ]);

        $this->assertSame(165, $summary['totalEvents']);
        $this->assertSame(3, $summary['uniqueCount']);
        $this->assertSame(['label' => 'Germany', 'count' => 120], $summary['topItem']);

        $emptySummary = $this->service->summarizePeriodRanking([]);
        $this->assertSame(0, $emptySummary['totalEvents']);
        $this->assertNull($emptySummary['topItem']);
    }

    public function testGetInOutReportDataResetsStaleDateHistoryAndBuildsSeries(): void
    {
        // Seed yesterday's history file to verify date-rollover reset
        file_put_contents(
            $this->tempCacheDir . 'analytics_history_in.json',
            json_encode(['date' => '1999-01-01', 'hours' => [0 => 999, 1 => 888]])
        );

        $realtimeTotal = $this->createMetricOnlyResponse('37');
        $realtimeMinutes = $this->createDimensionMetricResponse([
            ['0', '10'],
            ['5', '27'],
        ]);

        $this->propertiesMock->expects($this->exactly(2))
            ->method('runRealtimeReport')
            ->willReturnOnConsecutiveCalls($realtimeTotal, $realtimeMinutes);

        $yesterdayKey = date('Ymd', strtotime('-1 days'));
        $dailyResponse = $this->createDimensionMetricResponse([
            [$yesterdayKey, '150'],
        ]);

        $this->propertiesMock->expects($this->exactly(2))
            ->method('runReport')
            ->willReturn($dailyResponse);

        $report = $this->service->getInOutReportData('in', 'properties/111', $this->tempCacheDir, 45);

        $this->assertSame(37, $report['activeUsers']);
        $this->assertCount(24, $report['hourly']['labels']);
        $this->assertCount(24, $report['hourly']['values']);
        $this->assertNotContains(999, $report['hourly']['values']);
        $this->assertContains(37, $report['hourly']['values']);

        $this->assertCount(30, $report['minutes']['values']);
        $this->assertSame(10, $report['minutes']['values'][29]);
        $this->assertSame(27, $report['minutes']['values'][24]);

        $this->assertCount(7, $report['weekly']['values']);
        $this->assertSame(150, $report['weekly']['values'][6]);
        $this->assertCount(30, $report['monthly']['values']);
    }

    public function testGetLivePulseSummaryComputesNetFlow(): void
    {
        $inResp = $this->createMetricOnlyResponse('80');
        $outResp = $this->createMetricOnlyResponse('35');

        $this->propertiesMock->expects($this->exactly(2))
            ->method('runRealtimeReport')
            ->willReturnOnConsecutiveCalls($inResp, $outResp);

        $pulse = $this->service->getLivePulseSummary('properties/in', 'properties/out', $this->tempCacheDir, 45);

        $this->assertSame(80, $pulse['inbound']);
        $this->assertSame(35, $pulse['outbound']);
        $this->assertSame(45, $pulse['netFlow']);
        $this->assertSame(45, $pulse['ttl']);
    }

    public function testGetStandardReportDataRejectsPathTraversalPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getStandardReportData('../evil', 'properties/123', 45, $this->tempCacheDir);
    }

    public function testGetInOutReportDataRejectsInvalidStreamType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getInOutReportData('../evil', 'properties/123', $this->tempCacheDir, 45);
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $rowsData
     */
    private function createReportResponse(array $rowsData): object
    {
        $rows = [];
        foreach ($rowsData as [$d0, $d1, $m0]) {
            $dim0 = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
            $dim0->method('getValue')->willReturn($d0);

            $dim1 = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
            $dim1->method('getValue')->willReturn($d1);

            $met0 = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
            $met0->method('getValue')->willReturn($m0);

            $row = $this->getMockBuilder(\stdClass::class)->addMethods(['getDimensionValues', 'getMetricValues'])->getMock();
            $row->method('getDimensionValues')->willReturn([$dim0, $dim1]);
            $row->method('getMetricValues')->willReturn([$met0]);
            $rows[] = $row;
        }

        $resp = $this->getMockBuilder(\stdClass::class)->addMethods(['getRows'])->getMock();
        $resp->method('getRows')->willReturn($rows);
        return $resp;
    }

    private function createMetricOnlyResponse(string $value): object
    {
        $met = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
        $met->method('getValue')->willReturn($value);

        $row = $this->getMockBuilder(\stdClass::class)->addMethods(['getMetricValues'])->getMock();
        $row->method('getMetricValues')->willReturn([$met]);

        $resp = $this->getMockBuilder(\stdClass::class)->addMethods(['getRows'])->getMock();
        $resp->method('getRows')->willReturn([$row]);
        return $resp;
    }

    /**
     * @param list<array{0: string, 1: string}> $pairs
     */
    private function createDimensionMetricResponse(array $pairs): object
    {
        $rows = [];
        foreach ($pairs as [$dimVal, $metVal]) {
            $dim = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
            $dim->method('getValue')->willReturn($dimVal);

            $met = $this->getMockBuilder(\stdClass::class)->addMethods(['getValue'])->getMock();
            $met->method('getValue')->willReturn($metVal);

            $row = $this->getMockBuilder(\stdClass::class)->addMethods(['getDimensionValues', 'getMetricValues'])->getMock();
            $row->method('getDimensionValues')->willReturn([$dim]);
            $row->method('getMetricValues')->willReturn([$met]);
            $rows[] = $row;
        }

        $resp = $this->getMockBuilder(\stdClass::class)->addMethods(['getRows'])->getMock();
        $resp->method('getRows')->willReturn($rows);
        return $resp;
    }
}

