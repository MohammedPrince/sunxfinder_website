<?php

namespace App\Support;

use App\Models\AdSale;
use App\Models\AppSlider;
use App\Models\CompanyCarousel;
use Carbon\CarbonInterface;
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
    public static function totals(CarbonInterface $from, CarbonInterface $to, ?int $companyId = null): array
    {
        $rows = DB::table(self::TABLE)
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->when($companyId !== null, function ($query) use ($companyId) {
                $ads = self::companyAdIds($companyId);

                $query->where(fn($q) => $q
                    ->where(fn($sliders) => $sliders->where('ad_type', 'slider')->whereIn('ad_id', $ads['slider']))
                    ->orWhere(fn($carousels) => $carousels->where('ad_type', 'carousel')->whereIn('ad_id', $ads['carousel'])));
            })
            ->groupBy('ad_type', 'ad_id')
            ->get(['ad_type', 'ad_id', ...self::sumColumns()]);

        $totals = [];

        foreach ($rows as $row) {
            $totals["{$row->ad_type}:{$row->ad_id}"] = self::numbers($row);
        }

        return $totals;
    }

    /** Totals for one ad, over its whole life when no dates are given. */
    public static function adTotals(string $type, int $id, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $row = DB::table(self::TABLE)
            ->where('ad_type', $type)
            ->where('ad_id', $id)
            ->when($from, fn($query) => $query->where('stat_date', '>=', $from->toDateString()))
            ->when($to, fn($query) => $query->where('stat_date', '<=', $to->toDateString()))
            ->first(self::sumColumns());

        return self::numbers($row);
    }

    /** All the ads of a sale added together, counting only the sale's dates. */
    public static function saleTotals(AdSale $sale): ?array
    {
        $ads = $sale->trackedAds();

        if (!$ads) {
            return null;
        }

        $totals = [];

        foreach ($ads as [$type, $id]) {
            foreach (self::adTotals($type, $id, $sale->starts_on, $sale->ends_on) as $name => $number) {
                $totals[$name] = ($totals[$name] ?? 0) + $number;
            }
        }

        return $totals;
    }

    /** A company's ads: those assigned to it, plus any linked to its sales. */
    public static function companyAdIds(int $companyId): array
    {
        $sales = AdSale::query()->where('company_id', $companyId);

        return [
            'slider' => AppSlider::withTrashed()->where('company_id', $companyId)->pluck('id')
                ->merge((clone $sales)->whereNotNull('app_slider_id')->pluck('app_slider_id'))
                ->unique()->values()->all(),
            'carousel' => CompanyCarousel::withTrashed()->where('company_id', $companyId)->pluck('id')
                ->merge((clone $sales)->whereNotNull('company_carousel_id')->pluck('company_carousel_id'))
                ->unique()->values()->all(),
        ];
    }

    private static function sumColumns(): array
    {
        return [
            DB::raw('SUM(views) as views'),
            DB::raw('SUM(clicks) as clicks'),
            DB::raw("SUM(CASE WHEN platform = 'app' THEN views ELSE 0 END) as app_views"),
            DB::raw("SUM(CASE WHEN platform = 'website' THEN views ELSE 0 END) as website_views"),
        ];
    }

    private static function numbers(?object $row): array
    {
        return [
            'views' => (int) ($row->views ?? 0),
            'clicks' => (int) ($row->clicks ?? 0),
            'app_views' => (int) ($row->app_views ?? 0),
            'website_views' => (int) ($row->website_views ?? 0),
        ];
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
