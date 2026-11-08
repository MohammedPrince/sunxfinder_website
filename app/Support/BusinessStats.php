<?php

namespace App\Support;

use App\Models\AdSale;
use App\Models\CustomerComplaint;
use App\Models\PackageOffer;
use App\Models\PackageOfferResponse;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    /**
     * Package offers: pushed in the period, how customers answered, and what was won.
     * Answers count by the day the customer answered, whenever the offer was sent.
     */
    public static function packageOffers(Carbon $from, Carbon $to, ?int $companyId = null): array
    {
        $offers = fn() => DB::table('tbl_package_offers as o')
            ->whereNull('o.deleted_at')
            ->when($companyId, fn($query) => $query->where('o.company_id', $companyId));

        $responses = fn() => DB::table('tbl_package_offer_responses as r')
            ->join('tbl_package_offers as o', 'o.id', '=', 'r.package_offer_id')
            ->whereNull('o.deleted_at')
            ->when($companyId, fn($query) => $query->where('o.company_id', $companyId))
            ->whereBetween('r.responded_at', [$from, $to]);

        $answers = $responses()->selectRaw('r.status as k, COUNT(*) as n')->groupBy('k')->pluck('n', 'k');
        $accepted = (int) ($answers[PackageOfferResponse::ACCEPTED] ?? 0);
        $declined = (int) ($answers[PackageOfferResponse::DECLINED] ?? 0);

        // Customers the offers were pushed to in the period (one push can reach many).
        $reached = (int) DB::table('tbl_customer_notifications as cn')
            ->join('tbl_notifications as n', 'n.id', '=', 'cn.notification_id')
            ->join('tbl_package_offers as o', 'o.id', '=', 'n.package_offer_id')
            ->whereNull('n.deleted_at')
            ->whereNull('o.deleted_at')
            ->when($companyId, fn($query) => $query->where('o.company_id', $companyId))
            ->whereBetween('n.created_at', [$from, $to])
            ->selectRaw('COUNT(DISTINCT n.package_offer_id, cn.customer_id) as n')
            ->value('n');

        $top = $responses()
            ->leftJoin('tbl_solar_companies as c', 'c.id', '=', 'o.company_id')
            ->groupBy('o.id', 'o.title_en', 'o.title_ar', 'o.price', 'o.currency', 'c.name')
            ->selectRaw("o.id, o.title_en, o.title_ar, o.price, o.currency, c.name as company,
                SUM(r.status = 'accepted') as accepted, SUM(r.status = 'declined') as declined")
            ->orderByDesc('accepted')
            ->orderByDesc('declined')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'title' => app()->getLocale() === 'ar' && filled($row->title_ar) ? $row->title_ar : $row->title_en,
                'company' => $row->company,
                'price' => (float) $row->price,
                'currency' => $row->currency,
                'accepted' => (int) $row->accepted,
                'declined' => (int) $row->declined,
            ])->all();

        return [
            'sent' => $offers()->whereBetween('o.sent_at', [$from, $to])->count(),
            'reached' => $reached,
            'accepted' => $accepted,
            'declined' => $declined,
            // Share of reached customers who accepted.
            'accept_rate' => $reached > 0 ? (int) round($accepted / $reached * 100) : null,
            // Accepted offers' prices, one row per currency.
            'won' => $responses()->where('r.status', PackageOfferResponse::ACCEPTED)
                ->selectRaw('o.currency, COUNT(*) as n, SUM(o.price) as total')->groupBy('o.currency')->orderByDesc('total')->get()
                ->map(fn($row) => ['currency' => $row->currency, 'count' => (int) $row->n, 'total' => (float) $row->total])->all(),
            'open_now' => $offers()->where('o.status', PackageOffer::STATUS_ACTIVE)->whereNotNull('o.sent_at')
                ->where(fn($query) => $query->whereNull('o.expires_at')->orWhere('o.expires_at', '>', now()))->count(),
            'drafts' => $offers()->whereNull('o.sent_at')->count(),
            'by_creator' => $companyId ? [] : $offers()->whereBetween('o.sent_at', [$from, $to])
                ->selectRaw('o.created_by_type as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'top' => $top,
        ];
    }

    /** One row per customer answer in the period, for the package offers CSV. */
    public static function packageOfferAnswers(Carbon $from, Carbon $to, ?int $companyId = null)
    {
        return DB::table('tbl_package_offer_responses as r')
            ->join('tbl_package_offers as o', 'o.id', '=', 'r.package_offer_id')
            ->leftJoin('tbl_solar_companies as sc', 'sc.id', '=', 'o.company_id')
            ->leftJoin('tbl_customers as c', 'c.id', '=', 'r.customer_id')
            ->leftJoin('tbl_projects as p', function ($join) {
                $join->on('p.quote_id', '=', 'r.quote_id')->whereNull('p.deleted_at');
            })
            ->whereNull('o.deleted_at')
            ->when($companyId, fn($query) => $query->where('o.company_id', $companyId))
            ->whereBetween('r.responded_at', [$from, $to])
            ->orderBy('r.responded_at')
            ->get([
                'o.id as offer_id', 'o.title_en', 'o.title_ar', 'o.created_by_type', 'sc.name as company',
                'o.max_load_kw', 'o.price', 'o.currency', 'r.status', 'r.responded_at', 'r.quote_id',
                'c.full_name', 'c.username', 'c.city', 'p.id as project_id', 'p.status as project_status',
            ]);
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
