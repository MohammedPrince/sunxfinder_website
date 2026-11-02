<?php

namespace App\Support;

use App\Models\AdSale;
use App\Models\CustomerComplaint;
use App\Models\Rating;
use Carbon\Carbon;

/**
 * Advertising, rating and complaint figures shared by the admin and company dashboards and reports.
 * With a company id, only that company's records count, and only ratings it is allowed to see.
 */
class BusinessStats
{
    public static function advertising(Carbon $from, Carbon $to, ?int $companyId = null): array
    {
        $sales = fn() => AdSale::query()->when($companyId, fn($query) => $query->where('company_id', $companyId));
        $created = fn() => $sales()->whereBetween('created_at', [$from, $to]);

        $tracked = AdTracker::totals($from, $to, $companyId);
        $views = array_sum(array_column($tracked, 'views'));
        $clicks = array_sum(array_column($tracked, 'clicks'));

        return [
            'sales' => $created()->count(),
            'by_product' => $created()->selectRaw('product as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_status' => $created()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            // Money received in the period, one row per currency.
            'paid' => $sales()->where('status', 'paid')->whereBetween('paid_at', [$from, $to])
                ->selectRaw('currency, COUNT(*) as n, SUM(amount) as total')->groupBy('currency')->orderByDesc('total')->get()
                ->map(fn($row) => ['currency' => $row->currency, 'count' => (int) $row->n, 'total' => (float) $row->total])->all(),
            'active' => $sales()->where('status', 'paid')
                ->where(fn($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', today()))
                ->where(fn($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))->count(),
            'requested' => $sales()->where('status', 'requested')->count(),
            'awaiting_payment' => $sales()->where('status', 'awaiting_payment')->count(),
            'ending_soon' => $sales()->endingSoon()->count(),
            'views' => $views,
            'clicks' => $clicks,
            'click_rate' => AdTracker::rate($views, $clicks),
        ];
    }

    public static function ratings(Carbon $from, Carbon $to, ?int $companyId = null): array
    {
        $query = fn() => self::ratingQuery($companyId)->whereBetween('created_at', [$from, $to]);
        $byStars = $query()->selectRaw('stars, COUNT(*) as n')->groupBy('stars')->pluck('n', 'stars');

        $stars = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $stars[$star . ' ★'] = (int) ($byStars[$star] ?? 0);
        }

        $count = array_sum($stars);

        return [
            'count' => $count,
            'average' => $count ? round((float) $query()->avg('stars'), 1) : null,
            'hidden' => $companyId ? null : $query()->where('is_visible', false)->count(),
            'by_stars' => $stars,
        ];
    }

    /** All-time average and count, as customers see it for a company. */
    public static function ratingOverall(?int $companyId = null): array
    {
        $count = self::ratingQuery($companyId)->count();

        return [
            'count' => $count,
            'average' => $count ? round((float) self::ratingQuery($companyId)->avg('stars'), 1) : null,
            'this_month' => self::ratingQuery($companyId)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    public static function complaints(Carbon $from, Carbon $to): array
    {
        $opened = fn() => CustomerComplaint::query()->whereBetween('created_at', [$from, $to]);

        return [
            'opened' => $opened()->count(),
            'open_now' => CustomerComplaint::query()->where('status', 'pending')->count(),
            'by_status' => $opened()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_category' => $opened()->selectRaw('category as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
        ];
    }

    private static function ratingQuery(?int $companyId)
    {
        return Rating::query()->when($companyId, fn($query) => $query->where('company_id', $companyId)->visible());
    }
}
