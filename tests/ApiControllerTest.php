<?php
declare(strict_types=1);

namespace Tests;

use App\AnalyticsService;
use App\ApiController;
use App\Logger;

class ApiControllerTest extends TestCase
{
    private AnalyticsService $serviceMock;
    private ApiController $controller;
    private string $tempLogFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceMock = $this->createMock(AnalyticsService::class);
        $this->tempLogFile = sys_get_temp_dir() . '/spcast_api_test_' . uniqid() . '.log';
        $logger = new Logger($this->tempLogFile);

        $this->controller = new ApiController(
            $this->serviceMock,
            $logger,
            'properties/in',
            'properties/out',
            'properties/gen',
            sys_get_temp_dir() . '/',
            static fn (string $text): string => $text
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempLogFile)) {
            unlink($this->tempLogFile);
        }
        parent::tearDown();
    }

    public function testRejectsNonGetMethodWith405(): void
    {
        $res = $this->controller->handle(['action' => 'pulse'], 'POST');
        $this->assertSame(405, $res['status']);
        $this->assertFalse($res['body']['ok']);
    }

    public function testPulseActionReturnsRealtimeSummary(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getLivePulseSummary')
            ->willReturn([
                'inbound' => 42,
                'outbound' => 12,
                'netFlow' => 30,
                'timestamp' => 1700000000,
                'ttl' => 45,
            ]);

        $res = $this->controller->handle(['action' => 'pulse'], 'GET');
        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['body']['ok']);
        $this->assertSame(42, $res['body']['pulse']['inbound']);
        $this->assertSame(30, $res['body']['pulse']['netFlow']);
    }

    public function testDimensionReportActionReturnsStructuredPayload(): void
    {
        $samplePeriods = [
            'today' => ['Germany' => 50, 'Austria' => 10],
            'yesterday' => ['Germany' => 80],
            '7days' => ['Germany' => 400],
            '30days' => ['Germany' => 1600],
        ];

        $this->serviceMock->expects($this->once())
            ->method('getStandardReportData')
            ->with('browser', 'properties/gen', 45)
            ->willReturn($samplePeriods);

        $this->serviceMock->method('summarizePeriodRanking')
            ->willReturn([
                'totalEvents' => 60,
                'uniqueCount' => 2,
                'topItem' => ['label' => 'Germany', 'count' => 50],
            ]);

        $res = $this->controller->handle(['action' => 'report', 'type' => '  BROWSER '], 'GET');

        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['body']['ok']);
        $this->assertSame('dimension', $res['body']['kind']);
        $this->assertSame('browser', $res['body']['type']);
        $this->assertTrue($res['body']['supportsDetailModal']);
        $this->assertArrayNotHasKey('faqLinkType', $res['body']);
        $this->assertSame('browser.php', $res['body']['pageUrl']);
        $this->assertSame(50, $res['body']['periods']['today']['Germany']);
    }

    public function testInOutReportActionReturnsStreamPayload(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getInOutReportData')
            ->with('in', 'properties/in')
            ->willReturn([
                'activeUsers' => 25,
                'timestamp' => 1700000000,
                'hourly' => ['labels' => ['00:00'], 'values' => [25]],
                'minutes' => ['labels' => ['0m'], 'values' => [5]],
                'weekly' => ['labels' => ['05.11'], 'values' => [120]],
                'monthly' => ['labels' => ['05.11'], 'values' => [450]],
            ]);

        $res = $this->controller->handle(['type' => 'in'], 'GET');

        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['body']['ok']);
        $this->assertSame('inout', $res['body']['kind']);
        $this->assertSame(25, $res['body']['activeUsers']);
        $this->assertArrayHasKey('minutes', $res['body']['periods']);
    }

    public function testCityReportDoesNotExposeRareLabels(): void
    {
        $this->serviceMock->method('getStandardReportData')->willReturn([
            'today' => ['Berlin' => 12, 'Rare City' => 1],
            'yesterday' => [],
            '7days' => [],
            '30days' => [],
        ]);
        $this->serviceMock->method('summarizePeriodRanking')->willReturn([
            'totalEvents' => 13,
            'uniqueCount' => 2,
            'topItem' => ['label' => 'Berlin', 'count' => 12],
        ]);

        $response = $this->controller->handle(['action' => 'report', 'type' => 'city'], 'GET');

        $this->assertSame(200, $response['status']);
        $this->assertSame('Other', $response['body']['aggregateLabel']);
        $this->assertSame(['Berlin' => 12, 'Other' => 1], $response['body']['periods']['today']);
        $this->assertArrayNotHasKey('Rare City', $response['body']['periods']['today']);
    }

    public function testRejectsInvalidOrArrayReportTypeWith400(): void
    {
        $resUnknown = $this->controller->handle(['action' => 'report', 'type' => 'sql_injection'], 'GET');
        $this->assertSame(400, $resUnknown['status']);
        $this->assertFalse($resUnknown['body']['ok']);

        $resArray = $this->controller->handle(['action' => 'report', 'type' => ['country']], 'GET');
        $this->assertSame(400, $resArray['status']);
        $this->assertFalse($resArray['body']['ok']);

        $resAction = $this->controller->handle(['action' => ['bad']], 'GET');
        $this->assertSame(400, $resAction['status']);
    }
}

