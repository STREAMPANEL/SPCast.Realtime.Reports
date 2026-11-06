<?php
declare(strict_types=1);

namespace App;

final class PublicReportFilter
{
    public const MINIMUM_GROUP_COUNT = 5;

    public static function usesAggregation(ReportType $type): bool
    {
        return $type === ReportType::City || $type === ReportType::Mount;
    }

    /**
     * @param array<string, array<string, int>> $periods
     * @return array<string, array<string, int>>
     */
    public static function filterPeriods(ReportType $type, array $periods, string $otherLabel): array
    {
        if (!self::usesAggregation($type)) {
            return $periods;
        }

        foreach ($periods as $period => $items) {
            $periods[$period] = self::filterPeriod($type, $items, $otherLabel);
        }

        return $periods;
    }

    /**
     * @param array<string, int> $items
     * @return array<string, int>
     */
    public static function filterPeriod(ReportType $type, array $items, string $otherLabel): array
    {
        if (!self::usesAggregation($type)) {
            return $items;
        }

        $visible = [];
        $otherCount = 0;
        foreach ($items as $label => $count) {
            $normalizedCount = max(0, (int) $count);
            if ($normalizedCount < self::MINIMUM_GROUP_COUNT) {
                $otherCount += $normalizedCount;
            } else {
                $visible[(string) $label] = $normalizedCount;
            }
        }

        if ($otherCount === 0) {
            return $visible;
        }

        $existingOtherCount = $visible[$otherLabel] ?? 0;
        unset($visible[$otherLabel]);
        $visible[$otherLabel] = $existingOtherCount + $otherCount;

        return $visible;
    }
}
