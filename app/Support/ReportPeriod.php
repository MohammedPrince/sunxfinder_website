<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Date range for the admin and company Reports pages, plus the previous period of the
 * same length for "vs previous" comparisons.
 */
class ReportPeriod
{
    public const PRESETS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'all' => 'All time',
        'custom' => 'Custom range',
    ];

    // Active projects with no progress update for this many days are listed as stalled.
    public const STALLED_DAYS = 14;

    /**
     * @param array    $filters        preset, and from/to for a custom range
     * @param callable $firstRecordAt  returns the start date for "All time"
     */
    public static function resolve(array $filters, callable $firstRecordAt): array
    {
        $preset = $filters['preset'] ?? '30d';
        $preset = array_key_exists($preset, self::PRESETS) ? $preset : '30d';
        $now = now();

        [$from, $to] = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'all' => [Carbon::parse($firstRecordAt())->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                Carbon::parse($filters['from'] ?? $now->copy()->subDays(29))->startOfDay(),
                Carbon::parse($filters['to'] ?? $now)->endOfDay(),
            ],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        // Previous period of the same length (none for all time).
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        return [
            'preset' => $preset,
            'label' => self::PRESETS[$preset],
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'previous_from' => $preset === 'all' ? null : $previousFrom,
            'previous_to' => $preset === 'all' ? null : $previousTo,
        ];
    }

    /**
     * Fill daily (up to ~2 months) or monthly buckets from per-series counts keyed by
     * bucket ('Y-m-d' or 'Y-m'). Use bucketFormat() in SQL to build those keys.
     *
     * @param array<string, \Illuminate\Support\Collection|array> $series
     */
    public static function trend(Carbon $from, Carbon $to, int $days, array $series): array
    {
        $monthly = self::isMonthly($days);
        $buckets = [];
        $cursor = $monthly ? $from->copy()->startOfMonth() : $from->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($to)) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->format('Y-m-d');

            $bucket = [
                'label' => $monthly ? $cursor->format('M Y') : $cursor->format('d M'),
                'short' => $monthly ? $cursor->format('M') : $cursor->format('d'),
            ];

            foreach ($series as $name => $counts) {
                $bucket[$name] = (int) ($counts[$key] ?? 0);
            }

            $buckets[] = $bucket;

            $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        return [
            'monthly' => $monthly,
            'buckets' => $buckets,
        ];
    }

    public static function isMonthly(int $days): bool
    {
        return $days > 62;
    }

    /** MySQL DATE_FORMAT pattern matching the trend bucket keys. */
    public static function bucketFormat(int $days): string
    {
        return self::isMonthly($days) ? '%Y-%m' : '%Y-%m-%d';
    }

    /** Percentage change, or null when there is nothing to compare with. */
    public static function change(int|float $current, int|float|null $previous): ?int
    {
        return ($previous !== null && $previous > 0)
            ? (int) round(($current - $previous) / $previous * 100)
            : null;
    }
}
