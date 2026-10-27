<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website maintenance mode (Admin → Settings). Public pages show the maintenance
 * page with HTTP 503 instead of their content. Logged-in admins still see the site,
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
        $visitorCount = $this->recordMaintenanceVisit();

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

    /** Count each browser session once while maintenance mode is enabled. */
    private function recordMaintenanceVisit(): int
    {
        $directory = storage_path('app/private');

        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return 0;
        }

        $handle = @fopen($directory . DIRECTORY_SEPARATOR . 'maintenance-visitors.txt', 'c+');

        if ($handle === false) {
            return 0;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return 0;
            }

            rewind($handle);
            $contents = trim((string) stream_get_contents($handle));
            $sessions = $contents === '' || ctype_digit($contents)
                ? []
                : array_filter(array_unique(preg_split('/\R/', $contents) ?: []));
            $sessionId = hash('sha256', session()->getId());

            if (!in_array($sessionId, $sessions, true)) {
                $sessions[] = $sessionId;
            }

            $count = count($sessions);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, implode(PHP_EOL, $sessions));
            fflush($handle);
            flock($handle, LOCK_UN);

            return $count;
        } finally {
            fclose($handle);
        }
    }
}
