<?php
declare(strict_types=1);

namespace Tests;

use App\PublicReportFilter;
use App\ReportType;
use App\SeoHelper;

class PublicReportFilterTest extends TestCase
{
    public function testRareCityGroupsAreCombinedWithoutChangingTotalEvents(): void
    {
        $periods = [
            'today' => ['Berlin' => 12, 'Rare A' => 1, 'Rare B' => 4],
            'yesterday' => ['Hamburg' => 6, 'Rare C' => 2],
        ];

        $filtered = PublicReportFilter::filterPeriods(ReportType::City, $periods, 'Andere');

        $this->assertSame(['Berlin' => 12, 'Andere' => 5], $filtered['today']);
        $this->assertSame(['Hamburg' => 6, 'Andere' => 2], $filtered['yesterday']);
        $this->assertSame(array_sum($periods['today']), array_sum($filtered['today']));
        $this->assertSame(array_sum($periods['yesterday']), array_sum($filtered['yesterday']));
        $this->assertArrayNotHasKey('Rare A', $filtered['today']);
    }

    public function testExistingAggregateLabelAndOtherReportTypesStayValid(): void
    {
        $mounts = ['Other' => 8, 'Station A' => 6, 'Rare' => 1];
        $this->assertSame(
            ['Station A' => 6, 'Other' => 9],
            PublicReportFilter::filterPeriod(ReportType::Mount, $mounts, 'Other')
        );
        $this->assertSame($mounts, PublicReportFilter::filterPeriod(ReportType::Country, $mounts, 'Other'));
    }

    public function testAggregateIsShownLastWithoutARegularRank(): void
    {
        $entries = SeoHelper::normalizeMapEntries(['Berlin' => 12, 'Andere' => 50], 'Andere');

        $this->assertSame('Berlin', $entries[0]['label']);
        $this->assertSame(1, $entries[0]['rank']);
        $this->assertSame('Andere', $entries[1]['label']);
        $this->assertSame(0, $entries[1]['rank']);
        $this->assertStringContainsString('&mdash;', SeoHelper::renderRankingRowsHtml($entries, 62, 'Empty'));
    }
}
