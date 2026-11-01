<?php

namespace App\Support;

use App\Models\AppSlider;
use App\Models\CompanyCarousel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Daily view and click totals for sliders and carousel entries.
 * A view is one viewer seeing an ad on one day. Tracking never interrupts the page or app.
 */
class AdTracker
{
    public const TYPES = ['slider', 'carousel'];
    public const PLATFORMS = ['app', 'website'];

    private const TABLE = 'tbl_ad_daily_stats';

    /** Counts a list of ads as seen by one viewer, once a day. */
    public static function views(string $type, array $ids, string $platform, string $viewer): void
    {
        if (!$ids) {
            return;
        }

        try {
            $today = today()->toDateString();
            $key = 'ad-views:' . md5(implode('|', [$type, $platform, $viewer, $today, implode(',', $ids)]));

            if (Cache::add($key, 1, now()->endOfDay())) {
                self::bump($type, $ids, $platform, 'views');
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function click(string $type, int $id, string $platform): void
    {
        try {
            self::bump($type, [$id], $platform, 'clicks');
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Link previews and crawlers are not people seeing an ad. */
    public static function isBot(?string $userAgent): bool
    {
        return blank($userAgent)
            || (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget/i', $userAgent);
    }

    /** Totals per ad between two dates, keyed "type:id". */
    public static function totals(Carbon $from, Carbon $to, ?int $companyId = null): array
    {
        $rows = DB::table(self::TABLE)
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->when($companyId !== null, fn($query) => $query->where(fn($ads) => $ads
                ->where(fn($q) => $q->where('ad_type', 'slider')->whereIn('ad_id', AppSlider::withTrashed()->where('company_id', $companyId)->select('id')))
                ->orWhere(fn($q) => $q->where('ad_type', 'carousel')->whereIn('ad_id', CompanyCarousel::withTrashed()->where('company_id', $companyId)->select('id')))))
            ->groupBy('ad_type', 'ad_id')
            ->get(['ad_type', 'ad_id', DB::raw('SUM(views) as views'), DB::raw('SUM(clicks) as clicks')]);

        $totals = [];

        foreach ($rows as $row) {
            $totals["{$row->ad_type}:{$row->ad_id}"] = ['views' => (int) $row->views, 'clicks' => (int) $row->clicks];
        }

        return $totals;
    }

    /** Totals for one ad, over its whole life when no dates are given. */
    public static function adTotals(string $type, int $id, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $row = DB::table(self::TABLE)
            ->where('ad_type', $type)
            ->where('ad_id', $id)
            ->when($from, fn($query) => $query->where('stat_date', '>=', $from->toDateString()))
            ->when($to, fn($query) => $query->where('stat_date', '<=', $to->toDateString()))
            ->first([DB::raw('SUM(views) as views'), DB::raw('SUM(clicks) as clicks')]);

        return ['views' => (int) ($row->views ?? 0), 'clicks' => (int) ($row->clicks ?? 0)];
    }

    /** Clicks as a percentage of views, or null with no views yet. */
    public static function rate(int $views, int $clicks): ?float
    {
        return $views > 0 ? round($clicks / $views * 100, 1) : null;
    }

    private static function bump(string $type, array $ids, string $platform, string $column): void
    {
        $now = now();
        $today = $now->toDateString();

        $rows = array_map(fn($id) => [
            'ad_type' => $type,
            'ad_id' => (int) $id,
            'platform' => $platform,
            'stat_date' => $today,
            'views' => $column === 'views' ? 1 : 0,
            'clicks' => $column === 'clicks' ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        DB::table(self::TABLE)->upsert(
            $rows,
            ['ad_type', 'ad_id', 'platform', 'stat_date'],
            [$column => DB::raw("{$column} + 1"), 'updated_at' => $now]
        );
    }
}
