<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\MainService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website maintenance mode (Admin → Settings). Every public website page (home, company
 * profiles, sign-up, terms, privacy, delete account) shows the maintenance page with HTTP 503. Logged-in admins still see the site,
 * with a banner, so they can check it. Admin/company panels and the API are not affected.
 */
class SiteMaintenance
{
    /** Must keep working during maintenance: app links and their verification files. */
    private const ALWAYS_OPEN = ['app.company', 'app.assetlinks', 'app.aasa'];

    public function handle(Request $request, Closure $next): Response
    {
        $maintenance = Setting::maintenance();

        if (!$maintenance['enabled'] || $request->routeIs(...self::ALWAYS_OPEN)) {
            return $next($request);
        }

        if (auth('admin')->check()) {
            View::share('maintenancePreview', true);

            return $next($request);
        }

        $isArabic = app()->getLocale() === 'ar';
        // Same counter as the website footer.
        $visitorCount = app(MainService::class)->countVisit();

        return response()
            ->view('visitors.maintenance', [
                'isArabic' => $isArabic,
                'visitorCount' => $visitorCount,
                'title' => $isArabic
                    ? ($maintenance['title_ar'] ?: $maintenance['title_en'])
                    : ($maintenance['title_en'] ?: $maintenance['title_ar']),
                'message' => $isArabic
                    ? ($maintenance['message_ar'] ?: $maintenance['message_en'])
                    : ($maintenance['message_en'] ?: $maintenance['message_ar']),
                'until' => $maintenance['until'] ? \Illuminate\Support\Carbon::parse($maintenance['until']) : null,
            ], 503)
            // Tells search engines the outage is temporary.
            ->header('Retry-After', '3600');
    }
}
